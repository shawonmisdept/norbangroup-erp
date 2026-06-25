<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\GratuitySettlement;
use App\Models\User;
use Carbon\Carbon;

class GratuityCalculator
{
    private const MIN_YEARS = 5;

    private const DAYS_PER_YEAR = 30;

    private const WAGE_DIVISOR = 26;

    public function calculateOnSeparation(Employee $employee, User $user, ?Carbon $separationDate = null): ?GratuitySettlement
    {
        if (! in_array($employee->status, ['terminated', 'resigned'], true)) {
            return null;
        }

        $existing = GratuitySettlement::query()
            ->where('employee_id', $employee->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        if (! $employee->joining_date) {
            return null;
        }

        $separationDate ??= Carbon::today();
        $joinDate = Carbon::parse($employee->joining_date);
        $yearsOfService = round($joinDate->floatDiffInYears($separationDate), 2);

        if ($yearsOfService < self::MIN_YEARS) {
            return GratuitySettlement::create([
                'factory_id'        => $employee->factory_id,
                'employee_id'       => $employee->id,
                'separation_date'   => $separationDate,
                'years_of_service'  => $yearsOfService,
                'last_basic_salary' => $this->resolveLastBasic($employee),
                'gratuity_amount'   => 0,
                'status'            => 'calculated',
                'calculated_by'     => $user->id,
                'notes'             => 'Service below minimum ' . self::MIN_YEARS . ' years — no gratuity payable.',
            ]);
        }

        $lastBasic = $this->resolveLastBasic($employee);
        $completedYears = (int) floor($yearsOfService);
        $gratuityAmount = round(
            ($lastBasic / self::WAGE_DIVISOR) * self::DAYS_PER_YEAR * $completedYears,
            2
        );

        return GratuitySettlement::create([
            'factory_id'        => $employee->factory_id,
            'employee_id'       => $employee->id,
            'separation_date'   => $separationDate,
            'years_of_service'  => $yearsOfService,
            'last_basic_salary' => $lastBasic,
            'gratuity_amount'   => $gratuityAmount,
            'status'            => 'calculated',
            'calculated_by'     => $user->id,
            'notes'             => null,
        ]);
    }

    private function resolveLastBasic(Employee $employee): float
    {
        $employee->loadMissing('salaryStructure');
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

        return (float) ($structure->basic_salary ?: $structure->gross_salary);
    }
}
