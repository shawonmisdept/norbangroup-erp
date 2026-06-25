<?php

namespace App\Services\Hrm;

use App\Models\Hrm\BonusItem;
use App\Models\Hrm\BonusRun;
use App\Models\Hrm\Employee;
use App\Models\Hrm\PayrollItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FestivalBonusCalculator
{
    private const MIN_MONTHS_FOR_FULL = 6;

    public function calculate(BonusRun $run, User $user): BonusRun
    {
        return DB::transaction(function () use ($run, $user) {
            $year = (int) $run->year;
            $yearStart = Carbon::create($year, 1, 1)->startOfDay();
            $yearEnd = Carbon::create($year, 12, 31)->endOfDay();

            $employees = Employee::query()
                ->where('factory_id', $run->factory_id)
                ->whereIn('status', ['active', 'probation'])
                ->with('salaryStructure')
                ->get();

            BonusItem::where('bonus_run_id', $run->id)->delete();

            foreach ($employees as $employee) {
                $structure = $employee->salaryStructure;

                if (! $structure || ! $structure->is_active) {
                    continue;
                }

                $joinDate = $employee->joining_date
                    ? Carbon::parse($employee->joining_date)
                    : $yearStart;

                if ($joinDate->gt($yearEnd)) {
                    continue;
                }

                $workStart = $joinDate->lt($yearStart) ? $yearStart : $joinDate;
                $monthsWorked = max(1, (int) ceil($workStart->diffInDays($yearEnd) / 30));

                $basicAvg = $this->resolveBasicSalary($employee, $year);

                if ($basicAvg <= 0) {
                    continue;
                }

                $bonusAmount = $monthsWorked >= self::MIN_MONTHS_FOR_FULL
                    ? $basicAvg
                    : round($basicAvg * ($monthsWorked / 12), 2);

                BonusItem::create([
                    'bonus_run_id'  => $run->id,
                    'employee_id'   => $employee->id,
                    'basic_avg'     => $basicAvg,
                    'months_worked' => min(12, $monthsWorked),
                    'bonus_amount'  => $bonusAmount,
                ]);
            }

            $run->update([
                'status'         => 'calculated',
                'calculated_at'  => now(),
                'calculated_by'  => $user->id,
            ]);

            return $run->fresh(['items.employee']);
        });
    }

    private function resolveBasicSalary(Employee $employee, int $year): float
    {
        $structure = $employee->salaryStructure;

        if (! $structure) {
            return 0.0;
        }

        if ($structure->pay_type === 'wages') {
            return round((float) $structure->daily_wage * 26, 2);
        }

        $fromHead = $structure->headAmount('BASIC');

        if ($fromHead > 0) {
            return $fromHead;
        }

        if ((float) $structure->basic_salary > 0) {
            return (float) $structure->basic_salary;
        }

        $payrollBasic = PayrollItem::query()
            ->where('employee_id', $employee->id)
            ->whereHas('period', fn ($q) => $q
                ->where('factory_id', $employee->factory_id)
                ->where('year', $year))
            ->orderByDesc('id')
            ->value('basic_amount');

        return (float) ($payrollBasic ?? 0);
    }
}
