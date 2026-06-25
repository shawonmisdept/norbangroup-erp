<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\LeaveApproval;
use App\Models\Hrm\LeaveBalance;
use App\Models\Hrm\LeavePolicy;
use App\Models\Hrm\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public const STEP_REPORTING = 1;

    public const STEP_HR = 2;

    public function __construct(
        private EmployeeScheduleService $schedule,
        private HrmNotificationService $notifications,
    ) {}

    public const APPROVAL_STEPS = [
        self::STEP_REPORTING => 'Reporting Person',
        self::STEP_HR        => 'HR',
    ];

    public function ensureFactoryPolicies(int $factoryId): void
    {
        $leaveTypes = LeaveType::where('is_active', true)->get();

        foreach ($leaveTypes as $leaveType) {
            LeavePolicy::firstOrCreate(
                ['factory_id' => $factoryId, 'leave_type_id' => $leaveType->id],
                [
                    'days_per_year' => $leaveType->max_days_per_year ?? 0,
                    'is_active'     => true,
                ]
            );
        }
    }

    public function ensureEmployeeBalances(Employee $employee, ?int $year = null): void
    {
        $year = $year ?? (int) now()->year;
        $this->ensureFactoryPolicies($employee->factory_id);

        $policies = LeavePolicy::where('factory_id', $employee->factory_id)
            ->where('is_active', true)
            ->get();

        foreach ($policies as $policy) {
            LeaveBalance::firstOrCreate(
                [
                    'employee_id'   => $employee->id,
                    'leave_type_id' => $policy->leave_type_id,
                    'year'          => $year,
                ],
                [
                    'factory_id'    => $employee->factory_id,
                    'entitled_days' => $policy->days_per_year,
                ]
            );
        }
    }

    public function calculateLeaveDays(Carbon $startDate, Carbon $endDate, ?int $factoryId = null, ?Employee $employee = null): float
    {
        $holidayDates = [];

        if ($factoryId) {
            $holidayDates = Holiday::query()
                ->where('factory_id', $factoryId)
                ->where('is_active', true)
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->pluck('date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->all();
        }

        $days = 0;
        $period = CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay());

        foreach ($period as $date) {
            if ($employee && $this->schedule->isWeekend($employee, $date)) {
                continue;
            }

            if (! $employee && $date->isSunday()) {
                continue;
            }

            if (in_array($date->toDateString(), $holidayDates, true)) {
                continue;
            }

            $days++;
        }

        return (float) max(0, $days);
    }

    public function apply(Employee $employee, array $data, ?UploadedFile $attachment = null): LeaveApplication
    {
        $employee->loadMissing('reportingTo');

        if (! in_array($employee->status, ['active', 'probation'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Leave cannot be applied while your employment status is ' . ($employee->statusLabel() ?? $employee->status) . '.',
            ]);
        }

        if (! $employee->reporting_to_id) {
            throw ValidationException::withMessages([
                'reporting_to_id' => 'Your reporting person is not set. Please contact HR to update your employee profile.',
            ]);
        }

        if (! $employee->reportingTo) {
            throw ValidationException::withMessages([
                'reporting_to_id' => 'Your assigned reporting person is no longer available. Please contact HR.',
            ]);
        }

        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be on or after start date.',
            ]);
        }

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        $totalDays = $this->calculateLeaveDays($startDate, $endDate, $employee->factory_id, $employee);

        if ($totalDays <= 0) {
            throw ValidationException::withMessages([
                'start_date' => 'Selected dates do not include any working days (Sundays and factory holidays are excluded).',
            ]);
        }

        $this->ensureEmployeeBalances($employee, (int) $startDate->year);

        $policy = LeavePolicy::where('factory_id', $employee->factory_id)
            ->where('leave_type_id', $leaveType->id)
            ->where('is_active', true)
            ->first();

        if ($policy && $policy->min_days_notice > 0) {
            $noticeDays = now()->startOfDay()->diffInDays($startDate, false);

            if ($noticeDays < $policy->min_days_notice) {
                throw ValidationException::withMessages([
                    'start_date' => "This leave type requires at least {$policy->min_days_notice} day(s) notice.",
                ]);
            }
        }

        $attachmentRequired = $policy?->requires_attachment
            || ($policy?->requires_medical_after_days && $totalDays >= $policy->requires_medical_after_days);

        if ($attachmentRequired && ! $attachment) {
            throw ValidationException::withMessages([
                'attachment' => 'A supporting document is required for this leave application.',
            ]);
        }

        if ($leaveType->is_paid) {
            $balance = LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $startDate->year)
                ->first();

            if (! $balance || $balance->availableDays() < $totalDays) {
                throw ValidationException::withMessages([
                    'leave_type_id' => 'Insufficient leave balance for the selected dates.',
                ]);
            }
        }

        $this->assertNoOverlappingApplications($employee, $startDate, $endDate);

        $application = DB::transaction(function () use ($employee, $leaveType, $startDate, $endDate, $totalDays, $data, $attachment) {
            $attachmentPath = $attachment
                ? $attachment->store('hrm/leave-attachments', 'public')
                : null;

            $application = LeaveApplication::create([
                'factory_id'            => $employee->factory_id,
                'employee_id'           => $employee->id,
                'leave_type_id'         => $leaveType->id,
                'start_date'            => $startDate,
                'end_date'              => $endDate,
                'total_days'            => $totalDays,
                'reason'                => $data['reason'] ?? null,
                'attachment_path'       => $attachmentPath,
                'status'                => 'pending',
                'applied_at'            => now(),
                'current_approval_step' => self::STEP_REPORTING,
            ]);

            LeaveApproval::create([
                'leave_application_id' => $application->id,
                'step'                 => self::STEP_REPORTING,
                'step_label'           => self::APPROVAL_STEPS[self::STEP_REPORTING],
                'approver_employee_id' => $employee->reporting_to_id,
                'status'               => 'pending',
            ]);

            LeaveApproval::create([
                'leave_application_id' => $application->id,
                'step'                 => self::STEP_HR,
                'step_label'           => self::APPROVAL_STEPS[self::STEP_HR],
                'status'               => 'pending',
            ]);

            if ($leaveType->is_paid) {
                LeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $startDate->year)
                    ->increment('pending_days', $totalDays);
            }

            return $application->load(['leaveType', 'approvals', 'employee.reportingTo']);
        });

        $this->notifications->leaveApplied($application);

        return $application;
    }

    public function approveByEmployee(LeaveApplication $application, Employee $approver, ?string $notes = null): LeaveApplication
    {
        $this->assertCanEmployeeActOnStep($application, $approver, self::STEP_REPORTING);

        $application = DB::transaction(function () use ($application, $approver, $notes) {
            $this->markStepApproved($application, self::STEP_REPORTING, null, $approver, $notes);

            $application->update(['current_approval_step' => self::STEP_HR]);

            return $application->fresh(['leaveType', 'employee', 'approvals']);
        });

        $this->notifications->leavePendingHr($application);

        return $application;
    }

    public function approve(LeaveApplication $application, User $user, ?string $notes = null): LeaveApplication
    {
        if (! $application->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'This application is no longer pending.',
            ]);
        }

        if ($application->current_approval_step !== self::STEP_HR) {
            throw ValidationException::withMessages([
                'status' => 'Reporting person approval is required before HR can approve.',
            ]);
        }

        $application = DB::transaction(function () use ($application, $user, $notes) {
            $application->load('leaveType', 'employee');

            $this->markStepApproved($application, self::STEP_HR, $user, null, $notes);

            $application->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'approved_by' => $user->id,
            ]);

            if ($application->leaveType->is_paid) {
                $balance = LeaveBalance::where('employee_id', $application->employee_id)
                    ->where('leave_type_id', $application->leave_type_id)
                    ->where('year', $application->start_date->year)
                    ->lockForUpdate()
                    ->first();

                if ($balance) {
                    $balance->decrement('pending_days', $application->total_days);
                    $balance->increment('used_days', $application->total_days);
                }
            }

            $this->syncAttendanceForLeave($application);

            return $application->fresh(['leaveType', 'employee', 'approvals']);
        });

        $this->notifications->leaveStatusChanged($application, 'Approved');

        return $application;
    }

    public function rejectByEmployee(LeaveApplication $application, Employee $approver, string $reason): LeaveApplication
    {
        $this->assertCanEmployeeActOnStep($application, $approver, self::STEP_REPORTING);

        return $this->finalizeRejection($application, $reason, null, $approver);
    }

    public function reject(LeaveApplication $application, User $user, string $reason): LeaveApplication
    {
        if (! $application->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'This application is no longer pending.',
            ]);
        }

        if ($application->current_approval_step !== self::STEP_HR) {
            throw ValidationException::withMessages([
                'status' => 'This application is still awaiting reporting person approval.',
            ]);
        }

        return $this->finalizeRejection($application, $reason, $user, null);
    }

    public function cancel(LeaveApplication $application, Employee $employee): LeaveApplication
    {
        if ($application->employee_id !== $employee->id) {
            abort(403);
        }

        if (! $application->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending applications can be cancelled.',
            ]);
        }

        $application = DB::transaction(function () use ($application) {
            $application->load('leaveType');

            $application->update(['status' => 'cancelled']);

            $application->approvals()
                ->where('status', 'pending')
                ->update(['status' => 'skipped']);

            if ($application->leaveType->is_paid) {
                LeaveBalance::where('employee_id', $application->employee_id)
                    ->where('leave_type_id', $application->leave_type_id)
                    ->where('year', $application->start_date->year)
                    ->decrement('pending_days', $application->total_days);
            }

            return $application->fresh(['leaveType', 'approvals', 'employee']);
        });

        $this->notifications->leaveStatusChanged($application, 'Cancelled');

        return $application;
    }

    public function pendingApprovalsForManager(Employee $manager)
    {
        return LeaveApplication::query()
            ->with(['employee', 'leaveType'])
            ->where('status', 'pending')
            ->where('current_approval_step', self::STEP_REPORTING)
            ->whereHas('employee', fn ($query) => $query->where('reporting_to_id', $manager->id))
            ->latest('applied_at')
            ->get();
    }

    /**
     * HR bulk entry — creates an approved leave (skips employee workflow & notice rules).
     */
    public function recordBulkEntry(Employee $employee, array $data, User $hrUser): LeaveApplication
    {
        if (! in_array($employee->status, ['active', 'probation'], true)) {
            throw ValidationException::withMessages([
                'employee_code' => "Employee {$employee->employee_code} is not active.",
            ]);
        }

        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be on or after start date.',
            ]);
        }

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        $totalDays = $this->calculateLeaveDays($startDate, $endDate, $employee->factory_id, $employee);

        if ($totalDays <= 0) {
            throw ValidationException::withMessages([
                'start_date' => 'Selected dates do not include any working days.',
            ]);
        }

        $this->ensureEmployeeBalances($employee, (int) $startDate->year);
        $this->assertNoOverlappingApplications($employee, $startDate, $endDate);

        if ($leaveType->is_paid) {
            $balance = LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $startDate->year)
                ->first();

            if (! $balance || $balance->availableDays() < $totalDays) {
                throw ValidationException::withMessages([
                    'leave_type_id' => 'Insufficient leave balance for the selected dates.',
                ]);
            }
        }

        return DB::transaction(function () use ($employee, $leaveType, $startDate, $endDate, $totalDays, $data, $hrUser) {
            $application = LeaveApplication::create([
                'factory_id'            => $employee->factory_id,
                'employee_id'           => $employee->id,
                'leave_type_id'         => $leaveType->id,
                'start_date'            => $startDate,
                'end_date'              => $endDate,
                'total_days'            => $totalDays,
                'reason'                => $data['reason'] ?? 'Bulk entry by HR',
                'status'                => 'approved',
                'applied_at'            => now(),
                'approved_at'           => now(),
                'approved_by'           => $hrUser->id,
                'current_approval_step' => self::STEP_HR,
            ]);

            LeaveApproval::create([
                'leave_application_id' => $application->id,
                'step'                 => self::STEP_REPORTING,
                'step_label'           => self::APPROVAL_STEPS[self::STEP_REPORTING],
                'status'               => 'skipped',
                'notes'                => 'Bulk entry — workflow bypassed',
            ]);

            LeaveApproval::create([
                'leave_application_id' => $application->id,
                'step'                 => self::STEP_HR,
                'step_label'           => self::APPROVAL_STEPS[self::STEP_HR],
                'status'               => 'approved',
                'acted_by'             => $hrUser->id,
                'acted_at'             => now(),
                'notes'                => 'Bulk entry',
            ]);

            if ($leaveType->is_paid) {
                LeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $startDate->year)
                    ->increment('used_days', $totalDays);
            }

            $this->syncAttendanceForLeave($application);

            return $application->load(['leaveType', 'employee', 'approvals']);
        });
    }

    public function syncAttendanceForLeave(LeaveApplication $application): void
    {
        $application->loadMissing(['employee', 'leaveType']);
        $employee = $application->employee;

        if (! $employee) {
            return;
        }

        $holidayDates = Holiday::query()
            ->where('factory_id', $application->factory_id)
            ->where('is_active', true)
            ->whereDate('date', '>=', $application->start_date)
            ->whereDate('date', '<=', $application->end_date)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        $period = CarbonPeriod::create(
            $application->start_date->copy()->startOfDay(),
            $application->end_date->copy()->startOfDay()
        );

        foreach ($period as $date) {
            if ($this->schedule->isWeekend($employee, $date) || in_array($date->toDateString(), $holidayDates, true)) {
                continue;
            }

            $attendancePeriod = AttendancePeriod::getOrCreateForMonth(
                $application->factory_id,
                $date->year,
                $date->month
            );

            AttendanceDailyLog::updateOrCreate(
                [
                    'employee_id'     => $employee->id,
                    'attendance_date' => $date->toDateString(),
                ],
                [
                    'factory_id'           => $application->factory_id,
                    'attendance_period_id' => $attendancePeriod->id,
                    'shift_id'             => $employee->shift_id,
                    'status'               => 'leave',
                    'work_minutes'         => 0,
                    'late_minutes'         => 0,
                    'early_leave_minutes'  => 0,
                    'punch_count'           => 0,
                    'is_manual'            => true,
                    'notes'                => 'Leave: ' . $application->leaveType->name,
                ]
            );
        }
    }

    private function assertCanEmployeeActOnStep(LeaveApplication $application, Employee $approver, int $step): void
    {
        if (! $application->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'This application is no longer pending.',
            ]);
        }

        if ($application->current_approval_step !== $step) {
            throw ValidationException::withMessages([
                'status' => 'This application is not awaiting your approval.',
            ]);
        }

        $application->loadMissing('employee');

        if ($application->employee->reporting_to_id !== $approver->id) {
            abort(403);
        }
    }

    private function markStepApproved(
        LeaveApplication $application,
        int $step,
        ?User $user,
        ?Employee $employee,
        ?string $notes
    ): void {
        $application->approvals()
            ->where('step', $step)
            ->where('status', 'pending')
            ->update([
                'status'               => 'approved',
                'acted_by'             => $user?->id,
                'acted_by_employee_id' => $employee?->id,
                'acted_at'             => now(),
                'notes'                => $notes,
            ]);
    }

    private function finalizeRejection(
        LeaveApplication $application,
        string $reason,
        ?User $user,
        ?Employee $employee
    ): LeaveApplication {
        $application = DB::transaction(function () use ($application, $reason, $user, $employee) {
            $application->load('leaveType');

            $application->approvals()
                ->where('status', 'pending')
                ->update([
                    'status'               => 'rejected',
                    'acted_by'             => $user?->id,
                    'acted_by_employee_id' => $employee?->id,
                    'acted_at'             => now(),
                    'notes'                => $reason,
                ]);

            $application->update([
                'status'           => 'rejected',
                'rejected_at'      => now(),
                'rejected_by'      => $user?->id,
                'rejection_reason' => $reason,
            ]);

            if ($application->leaveType->is_paid) {
                LeaveBalance::where('employee_id', $application->employee_id)
                    ->where('leave_type_id', $application->leave_type_id)
                    ->where('year', $application->start_date->year)
                    ->decrement('pending_days', $application->total_days);
            }

            return $application->fresh(['leaveType', 'employee', 'approvals']);
        });

        $this->notifications->leaveStatusChanged($application, 'Rejected');

        return $application;
    }

    private function assertNoOverlappingApplications(Employee $employee, Carbon $startDate, Carbon $endDate): void
    {
        $overlap = LeaveApplication::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($inner) use ($startDate, $endDate) {
                        $inner->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'You already have a leave application for overlapping dates.',
            ]);
        }
    }
}
