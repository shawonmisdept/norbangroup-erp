<?php

namespace App\Http\Controllers\Admin\Hrm\Performance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\PerformanceCycle;
use App\Models\Hrm\PerformanceTemplate;
use App\Services\Hrm\PerformanceCycleService;
use App\Services\Hrm\PerformanceTemplateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CycleController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private PerformanceCycleService $cycles,
        private PerformanceTemplateService $templates,
    ) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = PerformanceCycle::query()
            ->with(['factory', 'template'])
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('cycle_type')) {
            $query->where('cycle_type', $request->cycle_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('admin.hrm.performance.cycles.index', [
            'cycles'            => $query->paginate(20)->withQueryString(),
            'factories'         => $this->factoryOptions($request),
            'scopedFactoryName' => $this->scopedFactoryName($request),
            'cycleTypes'        => PerformanceCycle::CYCLE_TYPES,
            'statuses'          => PerformanceCycle::STATUSES,
            'filters'           => $request->only(['factory_id', 'cycle_type', 'status']),
            'canManage'         => $request->user()?->hasPermission('hrm.performance.manage') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        $cycleType = $request->string('cycle_type', 'probation_6m')->toString();
        $suggested = $this->cycles->suggestPeriod($cycleType, (int) $request->input('year', now()->year));

        return view('admin.hrm.performance.cycles.form', [
            'cycle'      => new PerformanceCycle(array_merge($suggested, [
                'cycle_type' => $cycleType,
                'status'     => 'open',
            ])),
            'factories'  => $this->factoryOptions($request),
            'cycleTypes' => PerformanceCycle::CYCLE_TYPES,
            'templates'  => $this->templateOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'factory_id'  => ['required', 'exists:factories,id'],
            'cycle_type'  => ['required', Rule::in(array_keys(PerformanceCycle::CYCLE_TYPES))],
            'name'        => ['required', 'string', 'max:255'],
            'year'        => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'period_from' => ['required', 'date'],
            'period_to'   => ['required', 'date', 'after_or_equal:period_from'],
            'template_id' => ['nullable', 'exists:hrm_performance_templates,id'],
            'notes'       => ['nullable', 'string', 'max:5000'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $cycle = $this->cycles->open($validated, $request->user());

        return redirect()->route('admin.hrm.performance.cycles.show', $cycle)
            ->with('success', "Cycle opened — {$cycle->review_count} review(s) generated.");
    }

    public function show(Request $request, PerformanceCycle $cycle)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $cycle->factory_id);

        $cycle->load(['factory', 'template', 'openedByUser', 'reviews.employee']);

        return view('admin.hrm.performance.cycles.show', [
            'cycle'     => $cycle,
            'canManage' => $request->user()?->hasPermission('hrm.performance.manage') ?? false,
        ]);
    }

    public function close(Request $request, PerformanceCycle $cycle)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $cycle->factory_id);

        $this->cycles->close($cycle);

        return back()->with('success', 'Cycle closed.');
    }

    /** @return array<int, string> */
    private function templateOptions(Request $request): array
    {
        $query = PerformanceTemplate::query()->where('is_active', true)->orderBy('name');

        if ($request->user()?->factory_id) {
            $query->where(function ($q) use ($request) {
                $q->where('factory_id', $request->user()->factory_id)
                    ->orWhereNull('factory_id');
            });
        }

        return $query->get(['id', 'name'])
            ->mapWithKeys(fn (PerformanceTemplate $t) => [$t->id => $t->name])
            ->all();
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.view')) {
            abort(403, 'You do not have permission to view performance cycles.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.manage')) {
            abort(403, 'You do not have permission to manage performance cycles.');
        }
    }
}
