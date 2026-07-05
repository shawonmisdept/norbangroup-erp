<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;

class AttendanceBonusCalculator
{
    public function calculate(
        Employee $employee,
        int $absentDays,
        int $leaveDays,
        int $halfDayDays,
        int $lateDays,
    ): array {
        $employee->loadMissing('employmentType');

        if (! $employee->attendance_bonus_enabled) {
            return $this->result(0, false, 'Attendance bonus is not enabled for this employee.');
        }

        $amount = (float) $employee->attendance_bonus_amount;

        if ($amount <= 0) {
            return $this->result(0, false, 'Attendance bonus amount is not set.');
        }

        if ($employee->status === 'probation') {
            return $this->result(0, false, 'Employee is on probation.');
        }

        if ($employee->isTrainee()) {
            return $this->result(0, false, 'Trainee employees are not eligible for attendance bonus.');
        }

        if ($absentDays > 0) {
            return $this->result(0, false, "Absent days ({$absentDays}) disqualify attendance bonus.");
        }

        if ($leaveDays > 0) {
            return $this->result(0, false, "Leave days ({$leaveDays}) disqualify attendance bonus.");
        }

        if ($halfDayDays > 0) {
            return $this->result(0, false, "Half-day entries ({$halfDayDays}) disqualify attendance bonus.");
        }

        $maxLate = (int) config('hrm.payroll_processing.attendance_bonus_max_late_days', 3);

        if ($lateDays >= $maxLate) {
            return $this->result(0, false, "Late days ({$lateDays}) meet or exceed the limit ({$maxLate}).");
        }

        return $this->result(round($amount, 2), true, null);
    }

    /** @return array{amount: float, eligible: bool, reason: string|null} */
    private function result(float $amount, bool $eligible, ?string $reason): array
    {
        return [
            'amount'   => $amount,
            'eligible' => $eligible,
            'reason'   => $reason,
        ];
    }
}
