<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Shift;
use App\Support\PortalDateTime;
use Carbon\Carbon;

class EmployeeCheckInStatusService
{
    public function __construct(
        private ShiftWorkCalculator $shiftWork,
    ) {}

    /** @return array<string, mixed> */
    public function forEmployee(Employee $employee): array
    {
        $employee->loadMissing('factory', 'shift');

        $todayLog = AttendanceDailyLog::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->first();

        $nextAction = $this->resolveNextAction($employee->id);
        $shiftMinutes = $this->shiftWork->expectedShiftMinutes($employee->shift);
        $shiftLabel = $this->shiftLabel($employee->shift, $shiftMinutes);

        $status = 'idle';

        if ($todayLog?->check_in && $todayLog?->check_out) {
            $status = 'done';
        } elseif ($todayLog?->check_in && $nextAction === 'out') {
            $status = 'active';
        }

        $checkInAt = $todayLog?->check_in;
        $checkOutAt = $todayLog?->check_out;

        return [
            'status'          => $status,
            'next_action'     => $status === 'done' ? 'done' : $nextAction,
            'check_in_at'     => $checkInAt,
            'check_out_at'    => $checkOutAt,
            'check_in_iso'    => $checkInAt?->toIso8601String(),
            'check_out_iso'   => $checkOutAt?->toIso8601String(),
            'check_in_label'  => $checkInAt ? PortalDateTime::time($checkInAt) : null,
            'check_out_label' => $checkOutAt ? PortalDateTime::time($checkOutAt) : null,
            'work_minutes'    => (int) ($todayLog?->work_minutes ?? 0),
            'shift_minutes'   => $shiftMinutes,
            'shift_label'     => $shiftLabel,
            'mobile_enabled'  => (bool) ($employee->factory?->mobile_checkin_enabled ?? false),
        ];
    }

    public function resolveNextAction(int $employeeId): string
    {
        $lastPunch = AttendanceRawPunch::query()
            ->where('employee_id', $employeeId)
            ->whereIn('source', ['mobile_gps', 'qr_scan'])
            ->whereDate('punched_at', today())
            ->latest('punched_at')
            ->first();

        if (! $lastPunch || $lastPunch->punch_type === 'out') {
            return 'in';
        }

        return 'out';
    }

    public function expectedShiftMinutes(?Shift $shift): int
    {
        return $this->shiftWork->expectedShiftMinutes($shift);
    }

    public function shiftLabel(?Shift $shift, ?int $minutes = null): string
    {
        $minutes ??= $this->expectedShiftMinutes($shift);
        $hours = $minutes / 60;

        if (abs($hours - round($hours)) < 0.05) {
            return (int) round($hours) . '-hour shift';
        }

        return rtrim(rtrim(number_format($hours, 1), '0'), '.') . '-hour shift';
    }
}
