<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\Employee;
use App\Models\Hrm\LateAcceptanceApplication;
use App\Models\Hrm\SalaryStructure;
use Illuminate\Support\Collection;

class LateDeductionCalculator
{
    private const STANDARD_MONTH_DAYS = 26;

    /** @param Collection<int, AttendanceDailyLog> $logs */
    public function calculate(Employee $employee, SalaryStructure $structure, Collection $logs, ?AttendancePolicy $policy = null): array
    {
        $policy ??= AttendancePolicy::forFactory($employee->factory_id);
        $graceDays = max(0, (int) $policy->consecutive_late_grace_days);
        $dayRate = $this->oneDayRate($structure, $policy);

        $approvedDates = LateAcceptanceApplication::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereIn('attendance_date', $logs->pluck('attendance_date')->map(fn ($d) => $d->toDateString()))
            ->pluck('attendance_date')
            ->map(fn ($d) => $d->toDateString())
            ->flip();

        $streak = 0;
        $totalAmount = 0.0;
        $chargedDays = 0;
        $forgivenDays = 0;
        $streakByLogId = [];

        foreach ($logs->sortBy('attendance_date') as $log) {
            if ($log->status !== 'late') {
                if ($this->breaksStreak($log, $policy)) {
                    $streak = 0;
                }

                continue;
            }

            if ($this->isLateForgiven($employee, $log, $approvedDates)) {
                $forgivenDays++;
                $streak = 0;
                $streakByLogId[$log->id] = null;

                continue;
            }

            $streak++;
            $streakByLogId[$log->id] = $streak;

            if ($streak > $graceDays) {
                $totalAmount += $dayRate;
                $chargedDays++;
                $streak = 0;
            }
        }

        return [
            'amount'         => round($totalAmount, 2),
            'charged_days'   => $chargedDays,
            'forgiven_days'  => $forgivenDays,
            'day_rate'       => $dayRate,
            'grace_days'     => $graceDays,
            'streak_by_log'  => $streakByLogId,
        ];
    }

    /** @param Collection<string, int> $approvedDates */
    private function isLateForgiven(Employee $employee, AttendanceDailyLog $log, Collection $approvedDates): bool
    {
        if ($employee->late_acceptance_enabled) {
            return true;
        }

        if ($log->is_late_forgiven) {
            return true;
        }

        return $approvedDates->has($log->attendance_date->toDateString());
    }

    private function breaksStreak(AttendanceDailyLog $log, AttendancePolicy $policy): bool
    {
        if (in_array($log->status, ['present', 'leave', 'holiday', 'off_day'], true)) {
            return true;
        }

        if ($log->status === 'absent' && $policy->late_streak_resets_on_absent) {
            return true;
        }

        return false;
    }

    private function oneDayRate(SalaryStructure $structure, AttendancePolicy $policy): float
    {
        if ($structure->pay_type === 'wages') {
            return round((float) $structure->daily_wage, 2);
        }

        $basic = (float) $structure->headAmount('BASIC');

        if ($basic <= 0) {
            $basic = (float) $structure->basic_salary;
        }

        if ($policy->late_deduction_basis === 'gross' && (float) $structure->gross_salary > 0) {
            return round((float) $structure->gross_salary / self::STANDARD_MONTH_DAYS, 2);
        }

        if ($basic > 0) {
            return round($basic / self::STANDARD_MONTH_DAYS, 2);
        }

        $gross = (float) $structure->gross_salary;

        return $gross > 0 ? round($gross / self::STANDARD_MONTH_DAYS, 2) : 0.0;
    }
}
