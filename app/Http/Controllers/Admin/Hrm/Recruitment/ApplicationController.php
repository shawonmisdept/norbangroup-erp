<?php

namespace App\Http\Controllers\Admin\Hrm\Recruitment;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\JobPosting;
use App\Models\Hrm\RecruitmentApplication;
use App\Services\Hrm\RecruitmentDashboardService;
use App\Services\Hrm\RecruitmentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private RecruitmentService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = RecruitmentApplication::query()
            ->with(['jobPosting', 'factory', 'latestOffer'])
            ->latest('applied_at');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('job_posting_id')) {
            $query->where('job_posting_id', $request->job_posting_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('application_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('nid_number', 'like', "%{$search}%");
            });
        }

        $statsQuery = RecruitmentApplication::query();
        $this->scopeToUserFactory($statsQuery, $request);

        return view('admin.hrm.recruitment.applications.index', [
            'applications' => $query->paginate(20)->withQueryString(),
            'stats'        => [
                'applied'   => (clone $statsQuery)->where('status', 'applied')->count(),
                'screening' => (clone $statsQuery)->where('status', 'screening')->count(),
                'interview' => (clone $statsQuery)->where('status', 'interview')->count(),
            ],
            'factories'    => $this->factoryOptions($request),
            'postings'     => JobPosting::query()->when($request->user()?->factory_id, fn ($q, $fid) => $q->where('factory_id', $fid))->orderBy('title')->pluck('title', 'id'),
            'statuses'     => config('hrm.recruitment_statuses', []),
            'sources'      => config('hrm.recruitment_sources', []),
            'filters'      => $request->only(['factory_id', 'status', 'source', 'job_posting_id', 'search']),
            'canManage'    => $request->user()?->hasPermission('hrm.recruitment.applications.manage') ?? false,
            'canConvert'   => $request->user()?->hasPermission('hrm.recruitment.applications.convert') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        $postingId = $request->integer('job_posting_id') ?: null;

        return view('admin.hrm.recruitment.applications.form', [
            'application'      => new RecruitmentApplication(['source' => 'hr_manual']),
            'postings'         => $this->postingOptions($request),
            'selectedPosting'  => $postingId,
            'genders'          => config('hrm.employee_options.genders', []),
            'referralSources'  => config('hrm.recruitment_referral_sources', []),
            'isPublic'         => false,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $this->validateApplication($request);
        $posting = JobPosting::findOrFail($validated['job_posting_id']);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        $application = $this->service->submitApplication(
            $posting,
            $validated,
            $validated['source'] ?? 'hr_manual',
            $request->user(),
            $request->file('photo'),
            $request->file('nid_document'),
            $request->file('cv'),
        );

        return redirect()->route('admin.hrm.recruitment.applications.show', $application)
            ->with('success', 'Application recorded.');
    }

    public function edit(Request $request, RecruitmentApplication $application)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $application->factory_id);

        if (! $application->canEdit()) {
            return redirect()->route('admin.hrm.recruitment.applications.show', $application)
                ->with('error', 'This application is linked to an employee and cannot be edited.');
        }

        return view('admin.hrm.recruitment.applications.form', [
            'application'      => $application,
            'postings'         => $this->postingOptions($request),
            'selectedPosting'  => $application->job_posting_id,
            'genders'          => config('hrm.employee_options.genders', []),
            'referralSources'  => config('hrm.recruitment_referral_sources', []),
            'isPublic'         => false,
            'isEdit'           => true,
        ]);
    }

    public function update(Request $request, RecruitmentApplication $application)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $application->factory_id);

        if (! $application->canEdit()) {
            return back()->with('error', 'This application is linked to an employee and cannot be edited.');
        }

        $validated = $this->validateApplication($request);
        $posting = JobPosting::findOrFail($validated['job_posting_id']);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        if ($application->source === 'online') {
            $validated['source'] = 'online';
        }

        $this->service->updateApplication(
            $application,
            $posting,
            $validated,
            $request->user(),
            $request->file('photo'),
            $request->file('nid_document'),
            $request->file('cv'),
        );

        return redirect()->route('admin.hrm.recruitment.applications.show', $application)
            ->with('success', 'Application updated.');
    }

    public function destroy(Request $request, RecruitmentApplication $application)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $application->factory_id);

        if (! $application->canDelete()) {
            return back()->with('error', 'This application is linked to an employee and cannot be deleted.');
        }

        $this->service->deleteApplication($application);

        return redirect()->route('admin.hrm.recruitment.applications.index')
            ->with('success', 'Application deleted.');
    }

    public function show(Request $request, RecruitmentApplication $application)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $application->factory_id);

        $application->load(['jobPosting.department', 'jobPosting.designation', 'factory', 'convertedEmployee', 'reviewer', 'logs.user', 'interviews.scheduler', 'offerLetters.issuer']);

        return view('admin.hrm.recruitment.applications.show', [
            'application'    => $application,
            'formerEmployee' => $this->service->findFormerEmployee($application),
            'statuses'       => config('hrm.recruitment_statuses', []),
            'interviewTypes' => \App\Models\Hrm\RecruitmentInterview::TYPES,
            'interviewResults' => \App\Models\Hrm\RecruitmentInterview::RESULTS,
            'canManage'      => $request->user()?->hasPermission('hrm.recruitment.applications.manage') ?? false,
            'canConvert'     => $request->user()?->hasPermission('hrm.recruitment.applications.convert') ?? false,
            'canEdit'        => ($request->user()?->hasPermission('hrm.recruitment.applications.manage') ?? false) && $application->canEdit(),
            'canDelete'      => ($request->user()?->hasPermission('hrm.recruitment.applications.manage') ?? false) && $application->canDelete(),
        ]);
    }

    public function scheduleInterview(Request $request, RecruitmentApplication $application)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $application->factory_id);

        $validated = $request->validate([
            'scheduled_at'   => ['required', 'date', 'after:now'],
            'location'       => ['nullable', 'string', 'max:200'],
            'interview_type' => ['required', 'in:' . implode(',', array_keys(\App\Models\Hrm\RecruitmentInterview::TYPES))],
            'panel_notes'    => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->scheduleInterview($application, $validated, $request->user());

        return back()->with('success', 'Interview scheduled.');
    }

    public function completeInterview(Request $request, RecruitmentApplication $application, \App\Models\Hrm\RecruitmentInterview $interview)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $application->factory_id);

        if ($interview->application_id !== $application->id) {
            abort(404);
        }

        $validated = $request->validate([
            'result'      => ['required', 'in:' . implode(',', array_keys(\App\Models\Hrm\RecruitmentInterview::RESULTS))],
            'score'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'panel_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->service->completeInterview($interview, $validated, $request->user());

        return back()->with('success', 'Interview result recorded.');
    }

    public function updateStatus(Request $request, RecruitmentApplication $application)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $application->factory_id);

        $validated = $request->validate([
            'status'           => ['required', 'in:' . implode(',', array_keys(config('hrm.recruitment_statuses', [])))],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'rejection_reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000'],
        ]);

        $wasConverted = (bool) $application->converted_employee_id;

        $updated = $this->service->updateStatus(
            $application,
            $validated['status'],
            $request->user(),
            $validated['notes'] ?? null,
            $validated['rejection_reason'] ?? null,
        );

        if ($validated['status'] === 'hired' && ! $wasConverted && $updated->converted_employee_id) {
            return redirect()
                ->route('admin.hrm.employees.show', $updated->convertedEmployee)
                ->with('success', 'Application marked Hired and employee enrolled automatically.');
        }

        return back()->with('success', 'Application status updated.');
    }

    public function export(Request $request, RecruitmentDashboardService $dashboard): StreamedResponse
    {
        $this->ensureCanView($request);

        $filters = $request->only(['factory_id', 'status', 'source', 'job_posting_id', 'search', 'from', 'to']);
        $applications = $dashboard->filteredApplicationsQuery($request->user(), $filters)->get();

        $filename = 'recruitment-applications-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($applications) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Application No', 'Name', 'Phone', 'Email', 'Job', 'Factory', 'Source',
                'Status', 'Applied At', 'Expected Salary', 'NID', 'Rejection Reason',
            ]);

            foreach ($applications as $app) {
                fputcsv($handle, [
                    $app->application_no,
                    $app->name,
                    $app->phone,
                    $app->email,
                    $app->jobPosting?->title,
                    $app->factory?->name,
                    $app->sourceLabel(),
                    $app->statusLabel(),
                    $app->applied_at?->format('Y-m-d H:i'),
                    $app->expected_salary,
                    $app->nid_number,
                    $app->rejection_reason,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function convert(Request $request, RecruitmentApplication $application)
    {
        if (! $request->user()?->hasPermission('hrm.recruitment.applications.convert')) {
            abort(403);
        }

        $this->authorizeFactoryAccess($request, $application->factory_id);

        if (! $application->canConvert()) {
            return back()->with('error', 'Application must be Selected or Offered before conversion.');
        }

        $prefill = $this->service->employeePrefillFromApplication($application);

        return redirect()->route('admin.hrm.employees.create')
            ->with('recruitment_prefill', $prefill)
            ->with('recruitment_application_id', $application->id);
    }

    /** @return array<int, string> */
    private function postingOptions(Request $request): array
    {
        $query = JobPosting::query()->orderBy('title');
        $this->scopeToUserFactory($query, $request);

        return $query->get(['id', 'title', 'factory_id', 'status'])
            ->mapWithKeys(fn (JobPosting $p) => [$p->id => $p->title . ' (' . $p->statusLabel() . ')'])
            ->all();
    }

    /** @return array<string, mixed> */
    private function validateApplication(Request $request): array
    {
        return $request->validate([
            'job_posting_id'    => ['required', 'exists:hrm_job_postings,id'],
            'source'            => ['nullable', 'in:' . implode(',', array_keys(config('hrm.recruitment_sources', [])))],
            'name'              => ['required', 'string', 'max:200'],
            'phone'             => ['required', 'string', 'max:20'],
            'email'             => ['nullable', 'email', 'max:200'],
            'gender'            => ['nullable', 'in:' . implode(',', array_keys(config('hrm.employee_options.genders', [])))],
            'date_of_birth'     => ['nullable', 'date', 'before:today'],
            'nid_number'        => ['nullable', 'string', 'max:30'],
            'present_address'   => ['nullable', 'string', 'max:2000'],
            'permanent_address' => ['nullable', 'string', 'max:2000'],
            'photo'             => ['nullable', 'image', 'max:2048'],
            'nid_document'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'cv'                => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'education_history' => ['nullable', 'array'],
            'employment_history'=> ['nullable', 'array'],
            'expected_salary'   => ['nullable', 'numeric', 'min:0'],
            'referral_source'   => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.recruitment.applications.view')) {
            abort(403);
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.recruitment.applications.manage')) {
            abort(403);
        }
    }
}
