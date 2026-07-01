<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\Employee;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class HalfDayEntryService
{
    public function __construct(private EmployeeScheduleService $schedule) {}

    public function apply(Employee $employee, array $data, ?User $user = null): AttendanceDailyLog
    {
        $date = $data['attendance_date'];
        $period = AttendancePeriod::getOrCreateForMonth($employee->factory_id, (int) date('Y', strtotime($date)), (int) date('m', strtotime($date)));

        if ($period->isFrozen()) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Attendance period is frozen. Cannot add half day entry.',
            ]);
        }

        $existing = AttendanceDailyLog::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->first();

        if ($existing && in_array($existing->status, ['leave', 'holiday'], true)) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Cannot override leave or holiday with half day entry.',
            ]);
        }

        $payRatio = isset($data['half_day_pay_ratio']) && $data['half_day_pay_ratio'] !== ''
            ? (float) $data['half_day_pay_ratio']
            : $this->schedule->halfDayPayRatio($employee, null);

        if ($payRatio <= 0 || $payRatio > 1) {
            throw ValidationException::withMessages([
                'half_day_pay_ratio' => 'Pay ratio must be between 0.01 and 1.',
            ]);
        }

        return AttendanceDailyLog::updateOrCreate(
            [
                'employee_id'     => $employee->id,
                'attendance_date' => $date,
            ],
            [
                'factory_id'           => $employee->factory_id,
                'attendance_period_id' => $period->id,
                'shift_id'             => $employee->shift_id,
                'status'               => 'half_day',
                'half_day_type'        => $data['half_day_type'],
                'half_day_pay_ratio'   => $payRatio,
                'is_manual_half_day'   => true,
                'half_day_notes'       => $data['notes'] ?? null,
                'is_manual'            => true,
                'punch_count'          => $existing?->punch_count ?? 0,
                'work_minutes'         => $existing?->work_minutes ?? 0,
            ]
        );
    }

    public function remove(AttendanceDailyLog $log): void
    {
        $period = $log->period ?? AttendancePeriod::find($log->attendance_period_id);

        if ($period?->isFrozen()) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Attendance period is frozen. Cannot remove half day entry.',
            ]);
        }

        $log->delete();
    }
}
