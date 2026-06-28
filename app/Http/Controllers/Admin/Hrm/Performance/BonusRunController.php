<?php

namespace App\Http\Controllers\Admin\Hrm\Performance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\PerformanceBonusItem;
use App\Models\Hrm\PerformanceBonusRun;
use App\Models\Hrm\PerformanceCycle;
use App\Services\Hrm\PerformanceBonusCalculator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BonusRunController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private PerformanceBonusCalculator $calculator) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = PerformanceBonusRun::query()
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

        return view('admin.hrm.performance.bonus-runs.index', [
            'runs'              => $query->paginate(20)->withQueryString(),
            'factories'         => $this->factoryOptions($request),
            'scopedFactoryName' => $this->scopedFactoryName($request),
            'statuses'          => PerformanceBonusRun::STATUSES,
            'filters'           => $request->only(['factory_id', 'status']),
            'canManage'         => $request->user()?->hasPermission('hrm.performance.bonus.manage') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        $cycleId = $request->integer('cycle_id') ?: null;
        $cycle = $cycleId ? PerformanceCycle::find($cycleId) : null;

        return view('admin.hrm.performance.bonus-runs.form', [
            'run'       => new PerformanceBonusRun([
                'year'       => $cycle?->year ?? now()->year,
                'name'       => $cycle ? "Performance Bonus — {$cycle->name}" : 'Mid-Year Performance Bonus',
                'bonus_base' => config('hrm.performance.bonus_base_default', 'gross'),
                'status'     => 'draft',
                'performance_cycle_id' => $cycle?->id,
            ]),
            'factories' => $this->factoryOptions($request),
            'cycles'    => $this->cycleOptions($request),
            'bonusBases'=> PerformanceBonusRun::BONUS_BASES,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'factory_id'             => ['required', 'exists:factories,id'],
            'performance_cycle_id'   => ['nullable', 'exists:hrm_performance_cycles,id'],
            'year'                   => ['required', 'integer', 'min:2000', 'max:2100'],
            'name'                   => ['required', 'string', 'max:255'],
            'bonus_base'             => ['required', 'in:gross,basic'],
            'notes'                  => ['nullable', 'string', 'max:5000'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $run = PerformanceBonusRun::create($validated + [
            'status'     => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.hrm.performance.bonus-runs.show', $run)
            ->with('success', 'Performance bonus run created.');
    }

    public function show(Request $request, PerformanceBonusRun $bonusRun)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $bonusRun->factory_id);

        $bonusRun->load(['items.employee', 'items.review', 'factory', 'cycle', 'calculatedByUser', 'approvedByUser']);

        return view('admin.hrm.performance.bonus-runs.show', [
            'run'       => $bonusRun,
            'canManage' => $request->user()?->hasPermission('hrm.performance.bonus.manage') ?? false,
        ]);
    }

    public function calculate(Request $request, PerformanceBonusRun $bonusRun)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $bonusRun->factory_id);

        $this->calculator->calculate($bonusRun, $request->user());

        return redirect()->route('admin.hrm.performance.bonus-runs.show', $bonusRun)
            ->with('success', 'Performance bonus calculated from approved mid-year reviews.');
    }

    public function approve(Request $request, PerformanceBonusRun $bonusRun)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $bonusRun->factory_id);

        $this->calculator->approve($bonusRun, $request->user());

        return redirect()->route('admin.hrm.performance.bonus-runs.show', $bonusRun)
            ->with('success', 'Performance bonus run approved. Export available for payroll.');
    }

    public function updateItem(Request $request, PerformanceBonusRun $bonusRun, PerformanceBonusItem $item)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $bonusRun->factory_id);

        if ($item->performance_bonus_run_id !== $bonusRun->id) {
            abort(404);
        }

        $validated = $request->validate([
            'override_amount' => ['nullable', 'numeric', 'min:0'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ]);

        $override = $request->filled('override_amount') ? (float) $validated['override_amount'] : null;

        $this->calculator->updateItemOverride($item, $override, $validated['notes'] ?? null);

        return back()->with('success', 'Bonus amount updated.');
    }

    public function export(Request $request, PerformanceBonusRun $bonusRun): StreamedResponse
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $bonusRun->factory_id);

        if (! $bonusRun->isApproved()) {
            abort(403, 'Bonus run must be approved before export.');
        }

        $bonusRun->load('items.employee');

        $filename = sprintf('performance-bonus-%d-%s.csv', $bonusRun->year, now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($bonusRun) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Employee Code', 'Name', 'Score %', 'Band', 'Bonus %', 'Base Amount',
                'Calculated Bonus', 'Override', 'Final Amount', 'Salary Head',
            ]);

            foreach ($bonusRun->items as $item) {
                fputcsv($handle, [
                    $item->employee?->employee_code,
                    $item->employee?->name,
                    $item->overall_score,
                    $item->band_name,
                    $item->bonus_percent,
                    $item->base_amount,
                    $item->bonus_amount,
                    $item->override_amount,
                    $item->final_amount,
                    'PERFORMANCE BONUS',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array<int, string> */
    private function cycleOptions(Request $request): array
    {
        $query = PerformanceCycle::query()
            ->where('cycle_type', 'mid_year_6m')
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
        if (! $request->user()?->hasPermission('hrm.performance.bonus.view')) {
            abort(403, 'You do not have permission to view performance bonus runs.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.bonus.manage')) {
            abort(403, 'You do not have permission to manage performance bonus runs.');
        }
    }
}
