<?php

namespace App\Http\Controllers\Admin\Hrm\Performance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\PerformanceCycle;
use App\Models\Hrm\PerformanceTemplate;
use App\Services\Hrm\PerformanceTemplateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private PerformanceTemplateService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = PerformanceTemplate::query()->with('factory')->withCount('criteria')->orderBy('name');

        if ($request->user()?->factory_id) {
            $query->where(function ($q) use ($request) {
                $q->where('factory_id', $request->user()->factory_id)
                    ->orWhereNull('factory_id');
            });
        } elseif ($request->filled('factory_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('factory_id', $request->factory_id)
                    ->orWhereNull('factory_id');
            });
        }

        return view('admin.hrm.performance.templates.index', [
            'templates'         => $query->paginate(20)->withQueryString(),
            'factories'         => $this->factoryOptions($request),
            'scopedFactoryName' => $this->scopedFactoryName($request),
            'filters'           => $request->only(['factory_id']),
            'canManage'         => $request->user()?->hasPermission('hrm.performance.manage') ?? false,
            'cycleTypes'        => PerformanceCycle::CYCLE_TYPES,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        return view('admin.hrm.performance.templates.form', [
            'template'   => new PerformanceTemplate(['is_active' => true]),
            'factories'  => $this->factoryOptions($request),
            'cycleTypes' => PerformanceCycle::CYCLE_TYPES,
            'criteria'   => config('hrm.performance.default_criteria', []),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $this->validateTemplate($request);

        if (! empty($validated['factory_id'])) {
            $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        }

        $template = $this->service->create($validated, $request->user());

        return redirect()->route('admin.hrm.performance.templates.show', $template)
            ->with('success', 'Template created.');
    }

    public function show(Request $request, PerformanceTemplate $template)
    {
        $this->ensureCanView($request);

        if ($template->factory_id) {
            $this->authorizeFactoryAccess($request, $template->factory_id);
        }

        $template->load(['factory', 'criteria', 'createdByUser']);

        return view('admin.hrm.performance.templates.show', [
            'template'  => $template,
            'canManage' => $request->user()?->hasPermission('hrm.performance.manage') ?? false,
        ]);
    }

    public function edit(Request $request, PerformanceTemplate $template)
    {
        $this->ensureCanManage($request);

        if ($template->factory_id) {
            $this->authorizeFactoryAccess($request, $template->factory_id);
        }

        $template->load('criteria');

        return view('admin.hrm.performance.templates.form', [
            'template'   => $template,
            'factories'  => $this->factoryOptions($request),
            'cycleTypes' => PerformanceCycle::CYCLE_TYPES,
            'criteria'   => $template->criteria->map(fn ($c) => [
                'code'           => $c->code,
                'label'          => $c->label,
                'criterion_type' => $c->criterion_type,
                'weight'         => $c->weight,
                'sort_order'     => $c->sort_order,
            ])->all(),
        ]);
    }

    public function update(Request $request, PerformanceTemplate $template)
    {
        $this->ensureCanManage($request);

        if ($template->factory_id) {
            $this->authorizeFactoryAccess($request, $template->factory_id);
        }

        $validated = $this->validateTemplate($request);

        if (! empty($validated['factory_id'])) {
            $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        }

        $this->service->update($template, $validated);

        return redirect()->route('admin.hrm.performance.templates.show', $template)
            ->with('success', 'Template updated.');
    }

    /** @return array<string, mixed> */
    private function validateTemplate(Request $request): array
    {
        $validated = $request->validate([
            'factory_id'              => ['nullable', 'exists:factories,id'],
            'name'                    => ['required', 'string', 'max:255'],
            'cycle_types'             => ['nullable', 'array'],
            'cycle_types.*'           => [Rule::in(array_keys(PerformanceCycle::CYCLE_TYPES))],
            'is_default'              => ['nullable', 'boolean'],
            'is_active'               => ['nullable', 'boolean'],
            'criteria'                => ['required', 'array', 'min:1'],
            'criteria.*.code'         => ['required', 'string', 'max:40'],
            'criteria.*.label'        => ['required', 'string', 'max:255'],
            'criteria.*.criterion_type' => ['required', Rule::in(['auto', 'manual'])],
            'criteria.*.weight'       => ['required', 'numeric', 'min:0', 'max:100'],
            'criteria.*.sort_order'   => ['nullable', 'integer', 'min:0'],
        ]);

        $totalWeight = collect($validated['criteria'])->sum('weight');

        if (abs($totalWeight - 100) > 0.01) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'criteria' => 'Criteria weights must total 100%. Current total: ' . $totalWeight . '%',
            ]);
        }

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.view')) {
            abort(403, 'You do not have permission to view performance templates.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.manage')) {
            abort(403, 'You do not have permission to manage performance templates.');
        }
    }
}
