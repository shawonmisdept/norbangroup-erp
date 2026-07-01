<?php

namespace App\Services\Hrm;

use App\Models\Hrm\BonusItem;
use App\Models\Hrm\BonusRun;
use App\Models\Hrm\CanteenDeduction;
use App\Models\Hrm\Employee;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\PerformanceBonusItem;
use App\Models\Hrm\PerformanceBonusRun;
use App\Models\Hrm\ProductionIncentive;
use Illuminate\Support\Collection;

class PayrollAdjustmentResolver
{
    /** @return array{canteen: float, festival_bonus: float, performance_bonus: float, production_incentive: float, bonus_item_ids: list<int>, performance_bonus_item_ids: list<int>} */
    public function resolve(Employee $employee, PayrollPeriod $period): array
    {
        $festival = $this->resolveFestivalBonus($employee, $period);
        $performance = $this->resolvePerformanceBonus($employee, $period);

        return [
            'canteen'                    => $this->resolveCanteenDeduction($employee, $period),
            'festival_bonus'             => $festival['amount'],
            'performance_bonus'          => $performance['amount'],
            'production_incentive'       => $this->resolveProductionIncentiveShare($employee, $period),
            'bonus_item_ids'             => $festival['item_ids'],
            'performance_bonus_item_ids' => $performance['item_ids'],
        ];
    }

    public function clearPeriodLinks(PayrollPeriod $period): void
    {
        BonusItem::query()->where('payroll_period_id', $period->id)->update(['payroll_period_id' => null]);
        PerformanceBonusItem::query()->where('payroll_period_id', $period->id)->update(['payroll_period_id' => null]);
    }

    /** @param list<int> $bonusItemIds @param list<int> $performanceBonusItemIds */
    public function linkItems(PayrollPeriod $period, array $bonusItemIds, array $performanceBonusItemIds): void
    {
        if ($bonusItemIds !== []) {
            BonusItem::query()->whereIn('id', $bonusItemIds)->update(['payroll_period_id' => $period->id]);
        }

        if ($performanceBonusItemIds !== []) {
            PerformanceBonusItem::query()->whereIn('id', $performanceBonusItemIds)->update(['payroll_period_id' => $period->id]);
        }
    }

    private function resolveCanteenDeduction(Employee $employee, PayrollPeriod $period): float
    {
        return (float) CanteenDeduction::query()
            ->where('employee_id', $employee->id)
            ->where('factory_id', $period->factory_id)
            ->where('period_year', $period->year)
            ->where('period_month', $period->month)
            ->sum('amount');
    }

    /** @return array{amount: float, item_ids: list<int>} */
    private function resolveFestivalBonus(Employee $employee, PayrollPeriod $period): array
    {
        $items = BonusItem::query()
            ->where('employee_id', $employee->id)
            ->whereNull('payroll_period_id')
            ->whereHas('bonusRun', function ($query) use ($employee, $period) {
                $query->where('factory_id', $employee->factory_id)
                    ->where('status', 'approved')
                    ->where(function ($q) use ($period) {
                        $q->whereBetween('bonus_date', [$period->start_date, $period->end_date])
                            ->orWhere(function ($q2) use ($period) {
                                $q2->whereNull('bonus_date')->where('year', $period->year);
                            });
                    });
            })
            ->get(['id', 'bonus_amount']);

        return [
            'amount'   => round((float) $items->sum('bonus_amount'), 2),
            'item_ids' => $items->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    /** @return array{amount: float, item_ids: list<int>} */
    private function resolvePerformanceBonus(Employee $employee, PayrollPeriod $period): array
    {
        $items = PerformanceBonusItem::query()
            ->where('employee_id', $employee->id)
            ->whereNull('payroll_period_id')
            ->whereHas('run', function ($query) use ($employee, $period) {
                $query->where('factory_id', $employee->factory_id)
                    ->where('status', 'approved')
                    ->where(function ($q) use ($period) {
                        $q->whereYear('approved_at', $period->year)
                            ->whereMonth('approved_at', $period->month);
                    });
            })
            ->get(['id', 'final_amount', 'override_amount', 'bonus_amount']);

        $amount = $items->sum(fn (PerformanceBonusItem $item) => $item->resolvedAmount());

        return [
            'amount'   => round((float) $amount, 2),
            'item_ids' => $items->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    private function resolveProductionIncentiveShare(Employee $employee, PayrollPeriod $period): float
    {
        if (! $employee->line_id) {
            return 0.0;
        }

        $incentives = ProductionIncentive::query()
            ->where('factory_id', $employee->factory_id)
            ->where('line_id', $employee->line_id)
            ->where('period_year', $period->year)
            ->where('period_month', $period->month)
            ->where('status', 'approved')
            ->get();

        if ($incentives->isEmpty()) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($incentives as $incentive) {
            $headcount = max(1, $this->lineActiveHeadcount($employee->factory_id, (int) $employee->line_id));
            $total += (float) $incentive->total_amount / $headcount;
        }

        return round($total, 2);
    }

    private function lineActiveHeadcount(int $factoryId, int $lineId): int
    {
        return Employee::query()
            ->where('factory_id', $factoryId)
            ->where('line_id', $lineId)
            ->whereIn('status', ['active', 'probation'])
            ->count();
    }
}
