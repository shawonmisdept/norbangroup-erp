<?php

namespace App\Notifications;

use App\Models\Hrm\LeaveApplication;
use Illuminate\Notifications\Notification;

class LeaveAppliedAdminNotification extends Notification
{
    public function __construct(public LeaveApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $employee = $this->application->employee;
        $type = $this->application->leaveType;

        return [
            'type'    => 'hrm_leave_applied',
            'title'   => 'Leave Application Submitted',
            'message' => ($employee?->name ?? 'Employee') . ' applied for '
                . ($type?->name ?? 'leave') . ' (' . $this->application->total_days . ' day(s))',
            'url'     => route('admin.hrm.leave.transactions.show', $this->application),
        ];
    }
}
