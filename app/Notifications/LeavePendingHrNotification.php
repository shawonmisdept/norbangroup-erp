<?php

namespace App\Notifications;

use App\Models\Hrm\LeaveApplication;
use Illuminate\Notifications\Notification;

class LeavePendingHrNotification extends Notification
{
    public function __construct(public LeaveApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $employee = $this->application->employee;

        return [
            'type'    => 'hrm_leave_pending_hr',
            'title'   => 'Leave Awaiting HR Approval',
            'message' => ($employee?->name ?? 'Employee') . ' — reporting approved, HR action required',
            'url'     => route('admin.hrm.leave.transactions.show', $this->application),
        ];
    }
}
