<?php

namespace App\Http\Controllers\Admin\Hrm\Recruitment;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Hrm\JobPosting;
use App\Models\Hrm\WorkerCategory;
use App\Services\Hrm\JobPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobPostingController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private JobPostingService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = JobPosting::query()
            ->with(['factory', 'department', 'designation', 'workerCategory'])
            ->withCount('applications')
            ->latest();

        $this->scopeToUserFactory($query, $request);
        $this->service->applyIndexFilters($query, $request);

        $factories = $this->factoryOptions($request);

        return view('admin.hrm.recruitment.postings.index', [
            'postings'     => $query->paginate(20)->withQueryString(),
            'factories'    => $factories,
            'statuses'     => JobPosting::STATUSES,
            'filterOpts'   => $this->service->indexFilterOptions($request, $factories),
            'filters'      => $request->only([
                'factory_id', 'status', 'search', 'department_id', 'designation_id',
                'worker_category_id', 'shift_type', 'closing_soon', 'has_applications',
            ]),
            'canManage'    => $this->canManage($request),
            'canApprove'   => $request->user()?->canApproveRecruitmentPostings() ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        $templateKey = $request->string('template')->toString() ?: null;
        $defaults = $this->service->templateDefaults($templateKey);

        return view('admin.hrm.recruitment.postings.form', [
            'posting' => new JobPosting(array_merge([
                'status' => 'draft',
                'slots'  => 1,
            ], $defaults, ['template_key' => $templateKey])),
            ...$this->formOptions($request),
            'templates' => config('hrm.job_posting_templates', []),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $this->service->validatePosting($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $posting = $this->service->createPosting($validated, $request->user());

        return redirect()->route('admin.hrm.recruitment.postings.show', $posting)
            ->with('success', 'Job posting created.');
    }

    public function show(Request $request, JobPosting $posting)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        $posting->load(['factory', 'department', 'designation', 'workerCategory', 'creator', 'approver', 'logs.user']);
        $posting->loadCount('applications');

        return view('admin.hrm.recruitment.postings.show', [
            'posting'        => $posting,
            'analytics'      => $this->service->analytics($posting),
            'pipeline'       => $this->service->pipelineStats($posting),
            'statuses'       => config('hrm.recruitment_statuses', []),
            'canManage'      => $this->canManage($request),
            'canApprove'     => $request->user()?->canApproveRecruitmentPostings() ?? false,
        ]);
    }

    public function edit(Request $request, JobPosting $posting)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        return view('admin.hrm.recruitment.postings.form', [
            'posting'   => $posting,
            ...$this->formOptions($request, $posting->factory_id),
            'templates' => config('hrm.job_posting_templates', []),
        ]);
    }

    public function update(Request $request, JobPosting $posting)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        $validated = $this->service->validatePosting($request, $posting);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $this->service->updatePosting($posting, $validated, $request->user());

        return redirect()->route('admin.hrm.recruitment.postings.show', $posting)
            ->with('success', 'Job posting updated.');
    }

    public function destroy(Request $request, JobPosting $posting)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        if ($posting->applications()->exists()) {
            return back()->with('error', 'Cannot delete posting with applications. Close it instead.');
        }

        $posting->delete();

        return redirect()->route('admin.hrm.recruitment.postings.index')
            ->with('success', 'Job posting deleted.');
    }

    public function publish(Request $request, JobPosting $posting)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        $this->service->publish($posting, $request->user());

        return back()->with('success', $posting->fresh()->status === 'pending_approval'
            ? 'Posting submitted for approval.'
            : 'Job posting published.');
    }

    public function approve(Request $request, JobPosting $posting)
    {
        $this->ensureCanApprove($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        $this->service->approve($posting, $request->user());

        return back()->with('success', 'Job posting approved and published.');
    }

    public function close(Request $request, JobPosting $posting)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        $this->service->close($posting, $request->user(), $request->input('notes'));

        return back()->with('success', 'Job posting closed.');
    }

    public function reopen(Request $request, JobPosting $posting)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        $this->service->reopen($posting, $request->user());

        return back()->with('success', $posting->fresh()->status === 'pending_approval'
            ? 'Reopen submitted for approval.'
            : 'Job posting reopened.');
    }

    public function duplicate(Request $request, JobPosting $posting)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        $copy = $this->service->duplicate($posting, $request->user());

        return redirect()->route('admin.hrm.recruitment.postings.edit', $copy)
            ->with('success', 'Posting duplicated — review and save.');
    }

    public function bulkCreateForm(Request $request)
    {
        $this->ensureCanManage($request);

        $templateKey = $request->string('template')->toString() ?: 'sewing_operator';
        $defaults = $this->service->templateDefaults($templateKey);

        return view('admin.hrm.recruitment.postings.bulk', [
            'defaults'  => $defaults,
            'templateKey' => $templateKey,
            'factories' => $this->factoryOptions($request),
            'templates' => config('hrm.job_posting_templates', []),
            'statuses'  => JobPosting::STATUSES,
            'shiftTypes' => config('hrm.job_posting_shift_types', []),
        ]);
    }

    public function bulkStore(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'factory_ids'   => ['required', 'array', 'min:1'],
            'factory_ids.*' => ['integer', 'exists:factories,id'],
            'template_key'  => ['nullable', 'string', 'max:50'],
            'title'         => ['required', 'string', 'max:200'],
            'slots'         => ['required', 'integer', 'min:1', 'max:9999'],
            'shift_type'    => ['nullable', 'in:' . implode(',', array_keys(config('hrm.job_posting_shift_types', [])))],
            'status'        => ['required', 'in:draft,open,pending_approval'],
            'closes_at'     => ['nullable', 'date'],
            'requirements'  => ['nullable', 'string', 'max:50000'],
            'responsibilities' => ['nullable', 'string', 'max:50000'],
            'employment_status' => ['nullable', 'string', 'max:50000'],
        ]);

        foreach ($validated['factory_ids'] as $factoryId) {
            $this->authorizeFactoryAccess($request, (int) $factoryId);
        }

        $payload = [
            'title'             => $validated['title'],
            'slots'             => $validated['slots'],
            'shift_type'        => $validated['shift_type'] ?? null,
            'status'            => $validated['status'] === 'pending_approval' ? 'pending_approval' : $validated['status'],
            'closes_at'         => $validated['closes_at'] ?? null,
            'requirements'      => $validated['requirements'] ?? null,
            'responsibilities'  => $validated['responsibilities'] ?? null,
            'employment_status' => $validated['employment_status'] ?? null,
            'template_key'      => $validated['template_key'] ?? null,
            'salary_negotiable' => true,
            'is_internal'       => false,
            'rehire_eligible'   => false,
        ];

        $count = $this->service->bulkCreate($payload, $validated['factory_ids'], $request->user());

        return redirect()->route('admin.hrm.recruitment.postings.index')
            ->with('success', "{$count} job posting(s) created across selected units.");
    }

    public function formOptionsJson(Request $request): JsonResponse
    {
        $this->ensureCanView($request);

        $factoryId = (int) $request->validate(['factory_id' => ['required', 'exists:factories,id']])['factory_id'];
        $this->authorizeFactoryAccess($request, $factoryId);

        return response()->json($this->service->formOptionsForFactory($factoryId));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureCanView($request);

        $query = JobPosting::query()
            ->with(['factory', 'department', 'designation'])
            ->withCount('applications')
            ->latest();

        $this->scopeToUserFactory($query, $request);
        $this->service->applyIndexFilters($query, $request);

        $filename = 'job-postings-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Title', 'Factory', 'Status', 'Slots', 'Filled', 'Applications', 'Views',
                'Department', 'Designation', 'Shift', 'Published', 'Closes', 'Internal',
            ]);

            $query->chunk(100, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    $analytics = $this->service->analytics($row);
                    fputcsv($handle, [
                        $row->title,
                        $row->factory?->name,
                        $row->statusLabel(),
                        $row->slots,
                        $row->openings_filled,
                        $row->applications_count,
                        $analytics['page_views'],
                        $row->department?->name,
                        $row->designation?->name,
                        $row->shiftLabel(),
                        $row->published_at?->format('Y-m-d'),
                        $row->closes_at?->format('Y-m-d'),
                        $row->is_internal ? 'Yes' : 'No',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request, ?int $factoryId = null): array
    {
        $factoryId = $factoryId ?? old('factory_id', $request->user()?->factory_id);
        $scoped = $factoryId ? $this->service->formOptionsForFactory((int) $factoryId) : ['departments' => [], 'designations' => []];

        return [
            'factories'        => $this->factoryOptions($request),
            'departments'      => Department::where('is_active', true)
                ->with('factory')
                ->when($factoryId, fn ($q) => $q->where('factory_id', $factoryId))
                ->orderBy('name')
                ->get(),
            'designations'     => Designation::where('is_active', true)
                ->with('department.factory')
                ->when($factoryId, fn ($q) => $q->whereHas('department', fn ($dq) => $dq->where('factory_id', $factoryId)))
                ->orderBy('name')
                ->get(),
            'workerCategories' => WorkerCategory::where('is_active', true)->orderBy('name')->get(),
            'statuses'         => JobPosting::STATUSES,
            'shiftTypes'       => config('hrm.job_posting_shift_types', []),
            'postingGenders'   => config('hrm.recruitment_posting_genders', []),
            'defaultFactoryId' => $factoryId,
            'formOptionsUrl'   => route('admin.hrm.recruitment.postings.form-options'),
        ];
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.recruitment.postings.view')) {
            abort(403);
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $this->canManage($request)) {
            abort(403);
        }
    }

    private function ensureCanApprove(Request $request): void
    {
        if (! $request->user()?->canApproveRecruitmentPostings()) {
            abort(403);
        }
    }

    private function canManage(Request $request): bool
    {
        return $request->user()?->hasPermission('hrm.recruitment.postings.manage') ?? false;
    }
}
