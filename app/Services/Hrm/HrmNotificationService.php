<?php

namespace App\Services\Hrm;

use App\Mail\LeaveApplicationSubmittedMail;
use App\Mail\LeaveStatusMail;
use App\Models\AppSetting;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\BiometricDevice;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePromotion;
use App\Models\Hrm\EmployeeSeparation;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\FinalSettlement;
use App\Models\Hrm\LateAcceptanceApplication;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\LoanAccount;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\GatePass;
use App\Models\Hrm\ProxyPunchFlag;
use App\Models\Hrm\ShiftRoster;
use App\Models\Hrm\WorkerTransfer;
use App\Models\User;
use App\Notifications\BiometricSyncFailedNotification;
use App\Notifications\ContractExpiryNotification;
use App\Notifications\DailyAttendanceAlertNotification;
use App\Notifications\FinalSettlementApprovedNotification;
use App\Notifications\FinalSettlementCalculatedNotification;
use App\Notifications\FinalSettlementPendingNotification;
use App\Notifications\LeaveAppliedAdminNotification;
use App\Notifications\LeavePendingHrNotification;
use App\Notifications\OtLimitExceededNotification;
use App\Notifications\PortalLeaveApprovalRequiredNotification;
use App\Notifications\PortalLeaveStatusNotification;
use App\Notifications\LoanApplicationSubmittedNotification;
use App\Notifications\GatePassPendingNotification;
use App\Notifications\ManpowerVarianceNotification;
use App\Notifications\ProxyPunchFlaggedNotification;
use App\Notifications\WorkerTransferPendingNotification;
use App\Notifications\PortalAdvanceDisbursedNotification;
use App\Notifications\PortalFinalSettlementPaidNotification;
use App\Notifications\SeparationApprovedNotification;
use App\Notifications\SeparationPendingHrNotification;
use App\Notifications\SeparationRejectedNotification;
use App\Notifications\SeparationSubmittedNotification;
use App\Notifications\PortalPromotionStatusNotification;
use App\Notifications\PortalSeparationStatusNotification;
use App\Notifications\PortalLoanRejectedNotification;
use App\Notifications\PortalLoanSettledNotification;
use App\Notifications\PortalPayslipReadyNotification;
use App\Notifications\PortalRosterPublishedNotification;
use App\Notifications\PromotionApprovedNotification;
use App\Notifications\PromotionPendingNotification;
use App\Notifications\PromotionRejectedNotification;
use App\Notifications\ProbationEndNotification;
use App\Notifications\WorkingHoursExceededNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class HrmNotificationService
{
    public function lateAcceptanceApplied(LateAcceptanceApplication $application): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_late_acceptance) {
            return;
        }

        $application->loadMissing('employee');

        $this->notifyPermission(
            'hrm.attendance.approve',
            new \App\Notifications\LateAcceptanceAppliedNotification($application),
            $application->factory_id ?? null
        );
    }

    public function unmappedPunch(AttendanceRawPunch $punch): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_unmapped_punch) {
            return;
        }

        $factoryId = $punch->factory_id ?? 0;
        $cacheKey = 'hrm_unmapped_notify_' . $factoryId;

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes(10));

        $this->notifyPermission(
            'hrm.attendance.sync',
            new \App\Notifications\UnmappedBiometricPunchNotification($punch),
            $punch->factory_id ?? null
        );
    }

    public function manualPunchRecorded(AttendanceRawPunch $punch): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_manual_punch) {
            return;
        }

        $punch->loadMissing('employee');

        $this->notifyPermission(
            'hrm.attendance.view',
            new \App\Notifications\ManualPunchRecordedNotification($punch),
            $punch->factory_id ?? null
        );
    }

    public function leaveApplied(LeaveApplication $application): void
    {
        $settings = AppSetting::current();
        $application->loadMissing(['employee.reportingTo.portalUser', 'leaveType']);

        if ($settings->notify_popup_enabled && $settings->notify_popup_hrm_leave) {
            $this->notifyPermission(
                'hrm.leave.view',
                new LeaveAppliedAdminNotification($application),
                $application->factory_id
            );
        }

        $manager = $application->employee?->reportingTo;

        if ($manager?->portalUser) {
            $this->notifyPortalUser(
                $manager->portalUser,
                new PortalLeaveApprovalRequiredNotification($application)
            );
        }

        if ($settings->notify_mail_hrm_leave && $manager?->email) {
            $this->sendMail($manager->email, new LeaveApplicationSubmittedMail($application));
        }
    }

    public function leavePendingHr(LeaveApplication $application): void
    {
        $settings = AppSetting::current();
        $application->loadMissing(['employee', 'leaveType']);

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_leave) {
            return;
        }

        $this->notifyPermission(
            'hrm.leave.approve',
            new LeavePendingHrNotification($application),
            $application->factory_id
        );
    }

    public function leaveStatusChanged(LeaveApplication $application, string $statusLabel): void
    {
        $settings = AppSetting::current();
        $application->loadMissing(['employee.portalUser', 'leaveType']);

        $employee = $application->employee;

        if ($employee?->portalUser) {
            $this->notifyPortalUser(
                $employee->portalUser,
                new PortalLeaveStatusNotification($application, $statusLabel)
            );
        }

        if ($settings->notify_mail_hrm_leave && $employee?->email) {
            $this->sendMail($employee->email, new LeaveStatusMail($application, $statusLabel));
        }
    }

    public function syncFailed(BiometricDevice $device, string $errorMessage): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_sync_failed) {
            return;
        }

        $cacheKey = 'hrm_sync_fail_notify_' . $device->id;

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes(30));

        $this->notifyPermission(
            'hrm.attendance.sync',
            new BiometricSyncFailedNotification($device, $errorMessage),
            $device->factory_id
        );
    }

    public function payslipReady(PayrollItem $item): void
    {
        $item->loadMissing(['employee.portalUser', 'period']);
        $portalUser = $item->employee?->portalUser;

        if ($portalUser) {
            $this->notifyPortalUser($portalUser, new PortalPayslipReadyNotification($item));
        }
    }

    public function advanceDisbursed(LoanAccount $loan): void
    {
        $loan->loadMissing(['employee.portalUser']);

        if ($loan->employee?->portalUser) {
            $this->notifyPortalUser(
                $loan->employee->portalUser,
                new PortalAdvanceDisbursedNotification($loan)
            );
        }
    }

    public function loanSettled(LoanAccount $loan, float $amount): void
    {
        $loan->loadMissing(['employee.portalUser']);

        if ($loan->employee?->portalUser) {
            $this->notifyPortalUser(
                $loan->employee->portalUser,
                new PortalLoanSettledNotification($loan, $amount)
            );
        }
    }

    public function loanApplicationSubmitted(LoanAccount $loan): void
    {
        $loan->loadMissing('employee');

        $this->notifyPermission(
            'hrm.finance.manage',
            new LoanApplicationSubmittedNotification($loan),
            $loan->factory_id
        );
    }

    public function workerTransferPending(WorkerTransfer $transfer): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_worker_transfer) {
            return;
        }

        $transfer->loadMissing('employee');

        $this->notifyPermission(
            'hrm.rmg.worker-transfer.manage',
            new WorkerTransferPendingNotification($transfer),
            $transfer->factory_id
        );
    }

    public function promotionPending(EmployeePromotion $promotion): void
    {
        $promotion->loadMissing(['employee', 'toDesignation']);

        $this->notifyPermission(
            'hrm.employees.promotion.approve',
            new PromotionPendingNotification($promotion),
            $promotion->factory_id
        );
    }

    public function promotionApproved(EmployeePromotion $promotion): void
    {
        $promotion->loadMissing(['employee.portalUser']);

        if ($promotion->employee?->portalUser) {
            $this->notifyPortalUser(
                $promotion->employee->portalUser,
                new PortalPromotionStatusNotification($promotion, 'Approved')
            );
        }

        $this->notifyPermission(
            'hrm.employees.promotion.view',
            new PromotionApprovedNotification($promotion),
            $promotion->factory_id
        );
    }

    public function promotionRejected(EmployeePromotion $promotion): void
    {
        $promotion->loadMissing(['employee.portalUser']);

        if ($promotion->employee?->portalUser) {
            $this->notifyPortalUser(
                $promotion->employee->portalUser,
                new PortalPromotionStatusNotification($promotion, 'Rejected')
            );
        }

        $this->notifyPermission(
            'hrm.employees.promotion.view',
            new PromotionRejectedNotification($promotion),
            $promotion->factory_id
        );
    }

    public function gatePassPending(GatePass $gatePass): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_gate_pass) {
            return;
        }

        $gatePass->loadMissing('employee');

        $this->notifyPermission(
            'hrm.rmg.gate-pass.manage',
            new GatePassPendingNotification($gatePass),
            $gatePass->factory_id
        );
    }

    public function proxyPunchFlagged(ProxyPunchFlag $flag): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_proxy_punch) {
            return;
        }

        $flag->loadMissing(['employee', 'punch']);

        $this->notifyPermission(
            'hrm.rmg.proxy-punch.manage',
            new ProxyPunchFlaggedNotification($flag),
            $flag->factory_id
        );
    }

    public function manpowerVarianceIfNeeded(int $factoryId, string $planDate, array $summary): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_manpower_variance) {
            return;
        }

        $shortfall = collect($summary)->filter(fn (array $row) => ($row['variance'] ?? 0) < 0);

        if ($shortfall->isEmpty()) {
            return;
        }

        $cacheKey = 'hrm_manpower_variance_' . $factoryId . '_' . $planDate;

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addHours(12));

        $this->notifyPermission(
            'hrm.rmg.manpower-planning.view',
            new ManpowerVarianceNotification(
                $factoryId,
                $planDate,
                $shortfall->count(),
                (int) $shortfall->sum(fn (array $row) => abs($row['variance'] ?? 0))
            ),
            $factoryId
        );
    }

    public function loanRejected(LoanAccount $loan, ?string $reason = null): void
    {
        $loan->loadMissing(['employee.portalUser']);

        if ($loan->employee?->portalUser) {
            $this->notifyPortalUser(
                $loan->employee->portalUser,
                new PortalLoanRejectedNotification($loan, $reason)
            );
        }
    }

    public function rosterPublished(ShiftRoster $roster): void
    {
        $roster->loadMissing(['entries.employee.portalUser']);

        $notified = [];

        foreach ($roster->entries as $entry) {
            $portalUser = $entry->employee?->portalUser;

            if (! $portalUser || isset($notified[$portalUser->id])) {
                continue;
            }

            $this->notifyPortalUser($portalUser, new PortalRosterPublishedNotification($roster));
            $notified[$portalUser->id] = true;
        }
    }

    public function finalSettlementApproved(FinalSettlement $settlement): void
    {
        $settlement->loadMissing('employee');

        $this->notifyFinanceSettlementManagers(
            new FinalSettlementApprovedNotification($settlement),
            $settlement->factory_id
        );
    }

    public function finalSettlementCalculated(FinalSettlement $settlement): void
    {
        $settlement->loadMissing('employee');

        $this->notifyFinanceSettlementManagers(
            new FinalSettlementCalculatedNotification($settlement),
            $settlement->factory_id
        );
    }

    public function finalSettlementPending(Employee $employee): void
    {
        if (FinalSettlement::query()->where('employee_id', $employee->id)->exists()) {
            return;
        }

        $this->notifyFinanceSettlementManagers(
            new FinalSettlementPendingNotification($employee),
            $employee->factory_id
        );
    }

    public function finalSettlementPaid(FinalSettlement $settlement): void
    {
        $settlement->loadMissing(['employee.portalUser']);

        if ($settlement->employee?->portalUser) {
            $this->notifyPortalUser(
                $settlement->employee->portalUser,
                new PortalFinalSettlementPaidNotification($settlement)
            );
        }
    }

    public function separationSubmitted(EmployeeSeparation $separation): void
    {
        $separation->loadMissing(['employee.reportingTo.portalUser']);

        if ($separation->employee?->reportingTo?->portalUser) {
            $this->notifyPortalUser(
                $separation->employee->reportingTo->portalUser,
                new SeparationSubmittedNotification($separation)
            );
        }

        $this->notifyPermission(
            'hrm.employees.separation.view',
            new SeparationSubmittedNotification($separation),
            $separation->factory_id
        );
    }

    public function recruitmentApplicationSubmitted(\App\Models\Hrm\RecruitmentApplication $application): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_recruitment) {
            return;
        }

        $this->notifyPermission(
            'hrm.recruitment.applications.view',
            new \App\Notifications\RecruitmentApplicationSubmittedNotification($application),
            $application->factory_id
        );
    }

    public function separationPendingHr(EmployeeSeparation $separation): void
    {
        $this->notifyPermission(
            'hrm.employees.separation.approve',
            new SeparationPendingHrNotification($separation),
            $separation->factory_id
        );
    }

    public function separationApproved(EmployeeSeparation $separation): void
    {
        $separation->loadMissing(['employee.portalUser']);

        if ($separation->employee?->portalUser) {
            $this->notifyPortalUser(
                $separation->employee->portalUser,
                new PortalSeparationStatusNotification($separation, 'Approved')
            );
        }

        $this->notifyPermission(
            'hrm.employees.separation.view',
            new SeparationApprovedNotification($separation),
            $separation->factory_id
        );
    }

    public function separationRejected(EmployeeSeparation $separation): void
    {
        $separation->loadMissing(['employee.portalUser']);

        if ($separation->employee?->portalUser) {
            $this->notifyPortalUser(
                $separation->employee->portalUser,
                new PortalSeparationStatusNotification($separation, 'Rejected')
            );
        }
    }

    public function dailyAttendanceSummary(
        int $factoryId,
        string $factoryName,
        int $lateCount,
        int $absentCount,
        string $dateLabel,
        ?string $detail = null,
    ): void {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_daily_attendance) {
            return;
        }

        if ($lateCount === 0 && $absentCount === 0) {
            return;
        }

        $notification = new DailyAttendanceAlertNotification(
            $factoryName,
            $lateCount,
            $absentCount,
            $dateLabel,
            $detail
        );

        $this->notifyPermission('hrm.attendance.view', $notification, $factoryId);
    }

    public function lineChiefAttendanceAlert(
        Employee $manager,
        int $lateCount,
        int $absentCount,
        string $dateLabel,
    ): void {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_daily_attendance) {
            return;
        }

        if ($lateCount === 0 && $absentCount === 0) {
            return;
        }

        $manager->loadMissing('portalUser');

        if (! $manager->portalUser) {
            return;
        }

        $this->notifyPortalUser($manager->portalUser, new DailyAttendanceAlertNotification(
            'Your team',
            $lateCount,
            $absentCount,
            $dateLabel,
            'Reportees with late/absent attendance',
            route('employee.attendance')
        ));
    }

    public function contractExpiry(Employee $employee, int $daysRemaining): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_contract_expiry) {
            return;
        }

        $cacheKey = 'hrm_contract_expiry_' . $employee->id . '_' . $daysRemaining;

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addDay());

        $this->notifyPermission(
            'hrm.employees.view',
            new ContractExpiryNotification($employee, $daysRemaining),
            $employee->factory_id
        );
    }

    public function probationEnd(Employee $employee, int $daysRemaining): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_probation_end) {
            return;
        }

        $cacheKey = 'hrm_probation_end_' . $employee->id . '_' . $daysRemaining;

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addDay());

        $this->notifyPermission(
            'hrm.employees.view',
            new ProbationEndNotification($employee, $daysRemaining),
            $employee->factory_id
        );
    }

    public function otLimitExceeded(
        Employee $employee,
        float $otHours,
        float $limitHours,
        string $periodLabel,
    ): void {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_hrm_ot_limit) {
            return;
        }

        $cacheKey = 'hrm_ot_limit_' . $employee->id . '_' . md5($periodLabel);

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addDays(7));

        $this->notifyPermission(
            'hrm.salary.view',
            new OtLimitExceededNotification($employee, $otHours, $limitHours, $periodLabel),
            $employee->factory_id
        );
    }

    public function workingHoursExceeded(
        Employee $employee,
        float $hours,
        float $limitHours,
        string $periodLabel,
        string $periodType = 'daily',
    ): void {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled) {
            return;
        }

        $cacheKey = 'hrm_work_hours_' . $employee->id . '_' . $periodType . '_' . md5($periodLabel);

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addDays(3));

        $this->notifyPermission(
            'hrm.compliance.view',
            new WorkingHoursExceededNotification($employee, $hours, $limitHours, $periodLabel, $periodType),
            $employee->factory_id
        );
    }

    private function notifyFinanceSettlementManagers(Notification $notification, ?int $factoryId = null): void
    {
        $currentUserId = Auth::id();

        User::query()
            ->with('role')
            ->when($factoryId, fn ($q) => $q->where(function ($query) use ($factoryId) {
                $query->whereNull('factory_id')->orWhere('factory_id', $factoryId);
            }))
            ->get()
            ->filter(fn (User $user) => $user->hasPermission('hrm.finance.manage')
                || $user->hasPermission('hrm.finance.settlement.manage')
                || $user->canManageFinanceSubmodule('final-settlement'))
            ->filter(fn (User $user) => $currentUserId === null || $user->id !== $currentUserId)
            ->each(fn (User $user) => $user->notify($notification));
    }

    private function notifyPermission(string $permission, Notification $notification, ?int $factoryId = null, ?int $exceptUserId = null): void
    {
        $currentUserId = Auth::id();

        User::query()
            ->with('role')
            ->when($factoryId, fn ($q) => $q->where(function ($query) use ($factoryId) {
                $query->whereNull('factory_id')->orWhere('factory_id', $factoryId);
            }))
            ->get()
            ->filter(fn (User $user) => $user->hasPermission($permission))
            ->filter(fn (User $user) => $exceptUserId === null || $user->id !== $exceptUserId)
            ->filter(fn (User $user) => $currentUserId === null || $user->id !== $currentUserId)
            ->each(fn (User $user) => $user->notify($notification));
    }

    private function notifyPortalUser(EmployeePortalUser $portalUser, Notification $notification): void
    {
        if (! $portalUser->is_active) {
            return;
        }

        $portalUser->notify($notification);
    }

    private function sendMail(string $email, $mailable): void
    {
        if (! AppSetting::current()->canSendMail()) {
            return;
        }

        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable) {
            // mail failures should not block workflows
        }
    }
}
