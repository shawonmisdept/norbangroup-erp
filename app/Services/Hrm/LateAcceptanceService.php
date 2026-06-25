<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\Employee;
use App\Models\Hrm\LateAcceptanceApplication;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LateAcceptanceService
{
    public function __construct(private HrmNotificationService $notifications) {}

    public function apply(Employee $employee, array $data): LateAcceptanceApplication
    {
        $date = $data['attendance_date'];

        $log = AttendanceDailyLog::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->first();

        if (! $log || $log->status !== 'late') {
            throw ValidationException::withMessages([
                'attendance_date' => 'Late acceptance can only be applied for days marked as late.',
            ]);
        }

        if ($employee->late_acceptance_enabled) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Your profile already has standing late acceptance.',
            ]);
        }

        $existing = LateAcceptanceApplication::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->first();

        if ($existing && $existing->status !== 'rejected') {
            throw ValidationException::withMessages([
                'attendance_date' => 'An application already exists for this date.',
            ]);
        }

        if ($existing?->status === 'rejected') {
            $existing->update([
                'reason'            => $data['reason'] ?? null,
                'status'            => 'pending',
                'applied_at'        => now(),
                'approved_by'       => null,
                'approved_at'       => null,
                'rejected_by'       => null,
                'rejected_at'       => null,
                'rejection_reason'  => null,
            ]);

            $application = $existing->fresh();
            $this->notifications->lateAcceptanceApplied($application);

            return $application;
        }

        $application = LateAcceptanceApplication::create([
            'factory_id'      => $employee->factory_id,
            'employee_id'     => $employee->id,
            'attendance_date' => $date,
            'reason'          => $data['reason'] ?? null,
            'status'          => 'pending',
            'applied_at'      => now(),
        ]);

        $this->notifications->lateAcceptanceApplied($application);

        return $application;
    }

    public function approve(LateAcceptanceApplication $application, User $user): LateAcceptanceApplication
    {
        if (! $application->isPending()) {
            throw ValidationException::withMessages(['application' => 'Application is not pending.']);
        }

        $application->update([
            'status'      => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->syncDailyLog($application);

        return $application->fresh();
    }

    public function reject(LateAcceptanceApplication $application, User $user, string $reason): LateAcceptanceApplication
    {
        if (! $application->isPending()) {
            throw ValidationException::withMessages(['application' => 'Application is not pending.']);
        }

        $application->update([
            'status'            => 'rejected',
            'rejected_by'       => $user->id,
            'rejected_at'       => now(),
            'rejection_reason'  => $reason,
        ]);

        AttendanceDailyLog::query()
            ->where('employee_id', $application->employee_id)
            ->whereDate('attendance_date', $application->attendance_date)
            ->update([
                'is_late_forgiven'                => false,
                'late_acceptance_application_id'  => null,
            ]);

        return $application->fresh();
    }

    public function syncDailyLog(LateAcceptanceApplication $application): void
    {
        AttendanceDailyLog::query()
            ->where('employee_id', $application->employee_id)
            ->whereDate('attendance_date', $application->attendance_date)
            ->update([
                'is_late_forgiven'                => true,
                'late_acceptance_application_id'  => $application->id,
            ]);
    }
}
