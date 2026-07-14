<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeeSeparation;
use App\Models\Hrm\EmployeeSeparationApproval;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\ShiftRosterEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeSeparationService
{
    public const STEP_REPORTING = 1;

    public const STEP_HR = 2;

    public const APPROVAL_STEPS = [
        self::STEP_REPORTING => 'Reporting Person',
        self::STEP_HR        => 'HR',
    ];

    public function __construct(
        private GratuityCalculator $gratuityCalculator,
        private HrmNotificationService $notifications,
        private EmployeeServiceHistoryService $serviceHistory,
    ) {}

    /** @return array<string, string> */
    public function adminSeparationTypes(): array
    {
        return collect(config('hrm.separation_types', []))
            ->mapWithKeys(fn (array $meta, string $key) => [$key => $meta['label']])
            ->all();
    }

    /** @return array<string, string> */
    public function portalSeparationTypes(): array
    {
        return collect(config('hrm.separation_types', []))
            ->filter(fn (array $meta) => $meta['portal_allowed'] ?? false)
            ->mapWithKeys(fn (array $meta, string $key) => [$key => $meta['label']])
            ->all();
    }

    public function submit(
        Employee $employee,
        array $data,
        string $source = 'admin',
        ?User $initiatedBy = null,
        ?UploadedFile $attachment = null,
    ): EmployeeSeparation {
        $employee->loadMissing('reportingTo');

        if (! $employee->canInitiateSeparation()) {
            throw ValidationException::withMessages([
                'employee_id' => 'This employee cannot be separated while status is ' . $employee->statusLabel() . '.',
            ]);
        }

        if ($employee->pendingSeparation()->exists()) {
            throw ValidationException::withMessages([
                'employee_id' => 'A separation request is already pending for this employee.',
            ]);
        }

        $separationType = $data['separation_type'];

        if ($source === 'portal' && ! array_key_exists($separationType, $this->portalSeparationTypes())) {
            throw ValidationException::withMessages([
                'separation_type' => 'Invalid separation type for employee portal.',
            ]);
        }

        if ($source === 'admin' && ! array_key_exists($separationType, $this->adminSeparationTypes())) {
            throw ValidationException::withMessages([
                'separation_type' => 'Invalid separation type.',
            ]);
        }

        $applicationDate = Carbon::parse($data['application_date'] ?? now())->startOfDay();
        $lastWorkingDay = Carbon::parse($data['last_working_day'])->startOfDay();

        if ($lastWorkingDay->lt($applicationDate)) {
            throw ValidationException::withMessages([
                'last_working_day' => 'Last working day must be on or after application date.',
            ]);
        }

        $hasReporting = $employee->reporting_to_id && $employee->reportingTo;
        $startStep = $hasReporting ? self::STEP_REPORTING : self::STEP_HR;

        $separation = DB::transaction(function () use (
            $employee, $data, $source, $initiatedBy, $attachment,
            $separationType, $applicationDate, $lastWorkingDay, $hasReporting, $startStep
        ) {
            $attachmentPath = $attachment
                ? $attachment->store('hrm/separation-attachments', 'public')
                : null;

            $separation = EmployeeSeparation::create([
                'factory_id'             => $employee->factory_id,
                'employee_id'            => $employee->id,
                'separation_type'        => $separationType,
                'source'                 => $source,
                'status'                 => 'pending',
                'application_date'       => $applicationDate,
                'last_working_day'       => $lastWorkingDay,
                'notice_period_days'     => $data['notice_period_days'] ?? null,
                'reason'                 => $data['reason'] ?? null,
                'remarks'                => $data['remarks'] ?? null,
                'attachment_path'        => $attachmentPath,
                'current_approval_step'  => $startStep,
                'applied_at'             => now(),
                'initiated_by_user_id'   => $initiatedBy?->id,
                'exit_clearance'         => EmployeeSeparation::defaultExitClearance(),
            ]);

            EmployeeSeparationApproval::create([
                'employee_separation_id' => $separation->id,
                'step'                   => self::STEP_REPORTING,
                'step_label'             => self::APPROVAL_STEPS[self::STEP_REPORTING],
                'approver_employee_id'   => $employee->reporting_to_id,
                'status'                 => $hasReporting ? 'pending' : 'skipped',
            ]);

            EmployeeSeparationApproval::create([
                'employee_separation_id' => $separation->id,
                'step'                   => self::STEP_HR,
                'step_label'             => self::APPROVAL_STEPS[self::STEP_HR],
                'status'                 => 'pending',
            ]);

            return $separation->load(['employee.reportingTo', 'approvals']);
        });

        $this->notifications->separationSubmitted($separation);

        if (! $hasReporting) {
            $this->notifications->separationPendingHr($separation);
        }

        return $separation;
    }

    public function approveByEmployee(EmployeeSeparation $separation, Employee $approver, ?string $notes = null): EmployeeSeparation
    {
        $this->assertCanEmployeeActOnStep($separation, $approver, self::STEP_REPORTING);

        $separation = DB::transaction(function () use ($separation, $approver, $notes) {
            $this->markStepApproved($separation, self::STEP_REPORTING, null, $approver, $notes);
            $separation->update(['current_approval_step' => self::STEP_HR]);

            return $separation->fresh(['employee', 'approvals']);
        });

        $this->notifications->separationPendingHr($separation);

        return $separation;
    }

    public function approve(EmployeeSeparation $separation, User $user, ?string $notes = null): EmployeeSeparation
    {
        if (! $separation->isPending()) {
            throw ValidationException::withMessages(['status' => 'This separation request is no longer pending.']);
        }

        if ((int) $separation->current_approval_step !== self::STEP_HR) {
            throw ValidationException::withMessages(['status' => 'Reporting person approval is required before HR can approve.']);
        }

        if (! $separation->exitClearanceComplete()) {
            throw ValidationException::withMessages([
                'exit_clearance' => 'All exit clearance departments must be checked before HR approval.',
            ]);
        }

        $separation = DB::transaction(function () use ($separation, $user, $notes) {
            $this->markStepApproved($separation, self::STEP_HR, $user, null, $notes);

            $separation->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'approved_by' => $user->id,
            ]);

            $this->finalizeEmployeeSeparation($separation->fresh(['employee']), $user);

            return $separation->fresh(['employee', 'approvals']);
        });

        $this->notifications->separationApproved($separation);

        return $separation;
    }

    public function rejectByEmployee(EmployeeSeparation $separation, Employee $approver, string $reason): EmployeeSeparation
    {
        $this->assertCanEmployeeActOnStep($separation, $approver, self::STEP_REPORTING);

        return $this->finalizeRejection($separation, $reason, null, $approver);
    }

    public function reject(EmployeeSeparation $separation, User $user, string $reason): EmployeeSeparation
    {
        if (! $separation->isPending()) {
            throw ValidationException::withMessages(['status' => 'This separation request is no longer pending.']);
        }

        if ((int) $separation->current_approval_step !== self::STEP_HR) {
            throw ValidationException::withMessages(['status' => 'This request is still awaiting reporting person approval.']);
        }

        return $this->finalizeRejection($separation, $reason, $user, null);
    }

    public function cancel(EmployeeSeparation $separation, Employee $employee): EmployeeSeparation
    {
        if ((int) $separation->employee_id !== (int) $employee->id) {
            abort(403);
        }

        if (! $separation->isPending()) {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be cancelled.']);
        }

        $separation->update(['status' => 'cancelled']);

        return $separation->fresh();
    }

    public function cancelByAdmin(EmployeeSeparation $separation): EmployeeSeparation
    {
        if (! $separation->isPending()) {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be cancelled.']);
        }

        $separation->update(['status' => 'cancelled']);

        return $separation->fresh();
    }

    public function saveExitData(EmployeeSeparation $separation, array $data): EmployeeSeparation
    {
        if (! $separation->isPending() || (int) $separation->current_approval_step !== self::STEP_HR) {
            throw ValidationException::withMessages(['status' => 'Exit clearance can only be updated while awaiting HR approval.']);
        }

        $departments = array_keys(config('hrm.exit_clearance_departments', []));
        $clearance = [];
        foreach ($departments as $dept) {
            $clearance[$dept] = ! empty($data['exit_clearance'][$dept]);
        }

        $separation->update([
            'exit_clearance'        => $clearance,
            'exit_interview_notes'  => $data['exit_interview_notes'] ?? null,
            'exit_interview_at'     => filled($data['exit_interview_notes'] ?? null) ? now() : $separation->exit_interview_at,
        ]);

        return $separation->fresh();
    }

    /** @return \Illuminate\Support\Collection<int, EmployeeSeparation> */
    public function pendingApprovalsForManager(Employee $manager)
    {
        return EmployeeSeparation::query()
            ->with(['employee', 'approvals'])
            ->where('status', 'pending')
            ->where('current_approval_step', self::STEP_REPORTING)
            ->whereHas('approvals', fn ($q) => $q
                ->where('step', self::STEP_REPORTING)
                ->where('approver_employee_id', $manager->id)
                ->where('status', 'pending'))
            ->latest('applied_at')
            ->get();
    }

    private function finalizeEmployeeSeparation(EmployeeSeparation $separation, User $user): void
    {
        $employee = $separation->employee;
        $meta = config("hrm.separation_types.{$separation->separation_type}", []);
        $newStatus = $meta['employee_status'] ?? 'resigned';
        $lastDay = Carbon::parse($separation->last_working_day);

        $original = $employee->getOriginal();
        $employee->update([
            'status'           => $newStatus,
            'separation_date'  => $lastDay->toDateString(),
            'last_working_day' => $lastDay->toDateString(),
            'biometric_user_id' => null,
            'line_id'          => null,
            'shift_id'         => null,
        ]);

        $this->serviceHistory->recordChanges($employee->fresh(), $original);
        $this->serviceHistory->recordSeparation($employee->fresh(), $separation);

        LeaveApplication::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->update([
                'status'            => 'cancelled',
                'rejection_reason'  => 'Cancelled due to employee separation.',
            ]);

        ShiftRosterEntry::query()
            ->where('employee_id', $employee->id)
            ->whereDate('roster_date', '>', $lastDay->toDateString())
            ->delete();

        // Keep portal session active until the employee logs out; new logins are blocked via canLogin().

        $this->gratuityCalculator->calculateOnSeparation($employee->fresh(), $user, $lastDay);
        $this->notifications->finalSettlementPending($employee->fresh());
    }

    private function finalizeRejection(
        EmployeeSeparation $separation,
        string $reason,
        ?User $user,
        ?Employee $approverEmployee,
    ): EmployeeSeparation {
        return DB::transaction(function () use ($separation, $reason, $user, $approverEmployee) {
            $step = (int) $separation->current_approval_step;

            EmployeeSeparationApproval::query()
                ->where('employee_separation_id', $separation->id)
                ->where('step', $step)
                ->where('status', 'pending')
                ->update([
                    'status'               => 'rejected',
                    'acted_by'             => $user?->id,
                    'acted_by_employee_id' => $approverEmployee?->id,
                    'acted_at'             => now(),
                    'notes'                => $reason,
                ]);

            $separation->update([
                'status'            => 'rejected',
                'rejected_at'       => now(),
                'rejected_by'       => $user?->id,
                'rejection_reason'  => $reason,
            ]);

            $separation = $separation->fresh(['employee', 'approvals']);
            $this->notifications->separationRejected($separation);

            return $separation;
        });
    }

    private function markStepApproved(
        EmployeeSeparation $separation,
        int $step,
        ?User $user,
        ?Employee $approverEmployee,
        ?string $notes,
    ): void {
        EmployeeSeparationApproval::query()
            ->where('employee_separation_id', $separation->id)
            ->where('step', $step)
            ->update([
                'status'               => 'approved',
                'acted_by'             => $user?->id,
                'acted_by_employee_id' => $approverEmployee?->id,
                'acted_at'             => now(),
                'notes'                => $notes,
            ]);
    }

    private function assertCanEmployeeActOnStep(EmployeeSeparation $separation, Employee $approver, int $step): void
    {
        if (! $separation->isPending() || (int) $separation->current_approval_step !== $step) {
            throw ValidationException::withMessages(['status' => 'You cannot act on this separation request right now.']);
        }

        $approval = $separation->approvals()->where('step', $step)->first();

        if (! $approval || (int) $approval->approver_employee_id !== (int) $approver->id) {
            throw ValidationException::withMessages(['status' => 'You are not authorized to approve this step.']);
        }
    }
}
