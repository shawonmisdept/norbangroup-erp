<?php

namespace App\Http\Controllers\Admin\Hrm\Recruitment;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Hrm\JobPosting;
use App\Models\Hrm\WorkerCategory;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobPostingController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = JobPosting::query()
            ->with(['factory', 'department', 'designation', 'workerCategory'])
            ->withCount('applications')
            ->latest();

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        return view('admin.hrm.recruitment.postings.index', [
            'postings'  => $query->paginate(20)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'statuses'  => JobPosting::STATUSES,
            'filters'   => $request->only(['factory_id', 'status', 'search']),
            'canManage' => $request->user()?->hasPermission('hrm.recruitment.postings.manage') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        return view('admin.hrm.recruitment.postings.form', [
            'posting' => new JobPosting([
                'status' => 'draft',
                'slots'  => 1,
            ]),
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $this->validatePosting($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $posting = JobPosting::create([
            ...$validated,
            'created_by'   => $request->user()->id,
            'published_at' => $validated['status'] === 'open' ? now() : null,
        ]);

        return redirect()->route('admin.hrm.recruitment.postings.show', $posting)
            ->with('success', 'Job posting created.');
    }

    public function show(Request $request, JobPosting $posting)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        $posting->load(['factory', 'department', 'designation', 'workerCategory', 'creator']);
        $posting->loadCount('applications');

        return view('admin.hrm.recruitment.postings.show', [
            'posting'   => $posting,
            'canManage' => $request->user()?->hasPermission('hrm.recruitment.postings.manage') ?? false,
        ]);
    }

    public function edit(Request $request, JobPosting $posting)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        return view('admin.hrm.recruitment.postings.form', [
            'posting' => $posting,
            ...$this->formOptions($request, $posting->factory_id),
        ]);
    }

    public function update(Request $request, JobPosting $posting)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $posting->factory_id);

        $validated = $this->validatePosting($request, $posting);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        if ($validated['status'] === 'open' && ! $posting->published_at) {
            $validated['published_at'] = now();
        }

        $posting->update($validated);

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

    /** @return array<string, mixed> */
    private function formOptions(Request $request, ?int $factoryId = null): array
    {
        $factoryId = $factoryId ?? old('factory_id', $request->user()?->factory_id);

        return [
            'factories'        => $this->factoryOptions($request),
            'departments'      => Department::where('is_active', true)
                ->with('factory')
                ->when($factoryId, fn ($q) => $q->where('factory_id', $factoryId))
                ->orderBy('name')
                ->get(),
            'designations'     => Designation::where('is_active', true)
                ->with('department.factory')
                ->orderBy('name')
                ->get(),
            'workerCategories' => WorkerCategory::where('is_active', true)->orderBy('name')->get(),
            'statuses'         => JobPosting::STATUSES,
            'defaultFactoryId' => $factoryId,
        ];
    }

    private function validatePosting(Request $request, ?JobPosting $posting = null): array
    {
        $validated = $request->validate([
            'factory_id'         => ['required', 'exists:factories,id'],
            'department_id'      => ['nullable', 'exists:departments,id'],
            'designation_id'     => ['nullable', 'exists:designations,id'],
            'worker_category_id' => ['nullable', 'exists:hrm_worker_categories,id'],
            'title'              => ['required', 'string', 'max:200'],
            'description'        => ['nullable', 'string', 'max:50000'],
            'requirements'       => ['nullable', 'string', 'max:50000'],
            'skills_expertise'   => ['nullable', 'string', 'max:50000'],
            'responsibilities'   => ['nullable', 'string', 'max:50000'],
            'employment_status'  => ['nullable', 'string', 'max:50000'],
            'salary_text'        => [
                Rule::requiredIf(fn () => ! $request->boolean('salary_negotiable')),
                'nullable',
                'string',
                'max:500',
            ],
            'salary_negotiable'  => ['nullable', 'boolean'],
            'benefits'           => ['nullable', 'string', 'max:50000'],
            'slots'              => ['required', 'integer', 'min:1', 'max:9999'],
            'status'             => ['required', 'in:' . implode(',', array_keys(JobPosting::STATUSES))],
            'closes_at'          => ['nullable', 'date'],
        ]);

        $validated['salary_negotiable'] = $request->boolean('salary_negotiable');
        $validated['salary_text'] = trim(strip_tags($validated['salary_text'] ?? '')) ?: null;

        foreach (['description', 'requirements', 'skills_expertise', 'responsibilities', 'employment_status', 'benefits'] as $field) {
            $validated[$field] = HtmlSanitizer::clean($validated[$field] ?? null);
        }

        return $validated;
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.recruitment.postings.view')) {
            abort(403);
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.recruitment.postings.manage')) {
            abort(403);
        }
    }
}
