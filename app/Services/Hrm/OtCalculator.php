<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\Employee;

class OtCalculator
{
    public function __construct(
        private EmployeeScheduleService $schedule,
    ) {}

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Hrm\AttendanceDailyLog>  $logs
     * @return array{ot_hours: float, ot_amount: float, breakdown: array<string, float>}
     */
    public function calculate(Employee $employee, $logs, AttendancePolicy $policy, float $hourlyRate): array
    {
        $employee->loadMissing('shift');
        $isNightShift = (bool) $employee->shift?->is_night;
        $fullDayMinutes = (int) $policy->full_day_minutes;

        $normalMinutes = 0;
        $holidayMinutes = 0;
        $nightMinutes = 0;

        foreach ($logs as $log) {
            if ((int) $log->work_minutes <= 0) {
                continue;
            }

            $isWeekend = $this->schedule->isWeekend($employee, $log->attendance_date);
            $isHoliday = $this->schedule->isHoliday($employee->factory_id, $log->attendance_date);
            $isHolidayOt = ($isWeekend || $isHoliday) && $this->schedule->allowsWeekendOt($employee);

            $otMinutes = 0;

            if ($isHolidayOt) {
                $otMinutes = (int) $log->work_minutes;
            } elseif (in_array($log->status, ['present', 'late', 'half_day'], true)) {
                $otMinutes = max(0, (int) $log->work_minutes - $fullDayMinutes);
            }

            if ($otMinutes <= 0) {
                continue;
            }

            if ($isHolidayOt) {
                $holidayMinutes += $otMinutes;
            } elseif ($isNightShift) {
                $nightMinutes += $otMinutes;
            } else {
                $normalMinutes += $otMinutes;
            }
        }

        $normalMult = (float) ($policy->ot_multiplier_normal ?? 2.0);
        $holidayMult = (float) ($policy->ot_multiplier_holiday ?? 2.0);
        $nightMult = (float) ($policy->ot_multiplier_night ?? 2.0);

        $normalHours = round($normalMinutes / 60, 2);
        $holidayHours = round($holidayMinutes / 60, 2);
        $nightHours = round($nightMinutes / 60, 2);

        $normalAmount = round($normalHours * $hourlyRate * $normalMult, 2);
        $holidayAmount = round($holidayHours * $hourlyRate * $holidayMult, 2);
        $nightAmount = round($nightHours * $hourlyRate * $nightMult, 2);

        return [
            'ot_hours'  => round($normalHours + $holidayHours + $nightHours, 2),
            'ot_amount' => round($normalAmount + $holidayAmount + $nightAmount, 2),
            'breakdown' => [
                'normal_hours'   => $normalHours,
                'holiday_hours'  => $holidayHours,
                'night_hours'    => $nightHours,
                'normal_amount'  => $normalAmount,
                'holiday_amount' => $holidayAmount,
                'night_amount'   => $nightAmount,
            ],
        ];
    }
}
