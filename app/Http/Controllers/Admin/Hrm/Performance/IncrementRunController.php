<?php

namespace App\Http\Controllers\Admin\Hrm\Performance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\PerformanceCycle;
use App\Models\Hrm\PerformanceIncrementItem;
use App\Models\Hrm\PerformanceIncrementRun;
use App\Services\Hrm\PerformanceIncrementProcessor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncrementRunController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private PerformanceIncrementProcessor $processor) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = PerformanceIncrementRun::query()
            ->with(['factory', 'cycle'])
            ->latest('year')
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('admin.hrm.performance.increment-runs.index', [
            'runs'              => $query->paginate(20)->withQueryString(),
            'factories'         => $this->factoryOptions($request),
            'scopedFactoryName' => $this->scopedFactoryName($request),
            'statuses'          => PerformanceIncrementRun::STATUSES,
            'filters'           => $request->only(['factory_id', 'status']),
            'canManage'         => $request->user()?->hasPermission('hrm.performance.increment.manage') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        $cycleId = $request->integer('cycle_id') ?: null;
        $cycle = $cycleId ? PerformanceCycle::find($cycleId) : null;

        return view('admin.hrm.performance.increment-runs.form', [
            'run'       => new PerformanceIncrementRun([
                'year'                 => $cycle?->year ?? now()->year,
                'name'                 => $cycle ? "Annual Increment — {$cycle->name}" : 'Annual Increment Run',
                'status'               => 'draft',
                'performance_cycle_id' => $cycle?->id,
            ]),
            'factories' => $this->factoryOptions($request),
            'cycles'    => $this->cycleOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'factory_id'           => ['required', 'exists:factories,id'],
            'performance_cycle_id' => ['nullable', 'exists:hrm_performance_cycles,id'],
            'year'                 => ['required', 'integer', 'min:2000', 'max:2100'],
            'name'                 => ['required', 'string', 'max:255'],
            'notes'                => ['nullable', 'string', 'max:5000'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $run = PerformanceIncrementRun::create($validated + [
            'status'     => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.hrm.performance.increment-runs.show', $run)
            ->with('success', 'Increment run created.');
    }

    public function show(Request $request, PerformanceIncrementRun $incrementRun)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $incrementRun->factory_id);

        $incrementRun->load([
            'items.employee', 'items.review', 'items.salaryIncrementLog',
            'factory', 'cycle', 'calculatedByUser', 'appliedByUser',
        ]);

        return view('admin.hrm.performance.increment-runs.show', [
            'run'       => $incrementRun,
            'canManage' => $request->user()?->hasPermission('hrm.performance.increment.manage') ?? false,
        ]);
    }

    public function calculate(Request $request, PerformanceIncrementRun $incrementRun)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $incrementRun->factory_id);

        $this->processor->calculate($incrementRun, $request->user());

        return redirect()->route('admin.hrm.performance.increment-runs.show', $incrementRun)
            ->with('success', 'Increment suggestions calculated from approved annual reviews.');
    }

    public function apply(Request $request, PerformanceIncrementRun $incrementRun)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $incrementRun->factory_id);

        $result = $this->processor->apply($incrementRun, $request->user());

        $message = "Applied {$result['applied']} increment(s).";
        if ($result['skipped'] > 0) {
            $message .= " Skipped {$result['skipped']}.";
        }
        if ($result['failed'] > 0) {
            $message .= " Failed {$result['failed']}.";
        }

        return redirect()->route('admin.hrm.performance.increment-runs.show', $incrementRun)
            ->with($result['failed'] > 0 ? 'warning' : 'success', $message);
    }

    public function updateItem(Request $request, PerformanceIncrementRun $incrementRun, PerformanceIncrementItem $item)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $incrementRun->factory_id);

        if ($item->performance_increment_run_id !== $incrementRun->id) {
            abort(404);
        }

        $validated = $request->validate([
            'override_new_gross'         => ['nullable', 'numeric', 'min:0'],
            'override_increment_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'                      => ['nullable', 'string', 'max:2000'],
        ]);

        $this->processor->updateItemOverride(
            $item,
            isset($validated['override_new_gross']) ? (float) $validated['override_new_gross'] : null,
            isset($validated['override_increment_percent']) ? (float) $validated['override_increment_percent'] : null,
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Increment override saved.');
    }

    public function export(Request $request, PerformanceIncrementRun $incrementRun): StreamedResponse
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $incrementRun->factory_id);

        if (! $incrementRun->isApplied()) {
            abort(403, 'Increment run must be applied before export.');
        }

        $incrementRun->load('items.employee');

        $filename = sprintf('performance-increment-%d-%s.csv', $incrementRun->year, now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($incrementRun) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Employee Code', 'Name', 'Score %', 'Band', 'Increment %',
                'Previous Gross', 'New Gross', 'Increment Amount', 'Status',
            ]);

            foreach ($incrementRun->items as $item) {
                fputcsv($handle, [
                    $item->employee?->employee_code,
                    $item->employee?->name,
                    $item->overall_score,
                    $item->band_name,
                    $item->resolvedIncrementPercent(),
                    $item->previous_gross,
                    $item->final_new_gross,
                    $item->increment_amount,
                    $item->statusLabel(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array<int, string> */
    private function cycleOptions(Request $request): array
    {
        $query = PerformanceCycle::query()
            ->where('cycle_type', 'annual_12m')
            ->orderByDesc('year')
            ->orderByDesc('id');

        $this->scopeToUserFactory($query, $request);

        return $query->get(['id', 'name', 'factory_id', 'year'])
            ->mapWithKeys(fn (PerformanceCycle $c) => [
                $c->id => "{$c->name} ({$c->year}) — Factory #{$c->factory_id}",
            ])
            ->all();
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.increment.view')) {
            abort(403, 'You do not have permission to view increment runs.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.increment.manage')) {
            abort(403, 'You do not have permission to manage increment runs.');
        }
    }
}
