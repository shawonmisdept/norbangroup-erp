<?php

namespace App\Notifications;

use App\Models\Hrm\LeaveApplication;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use Illuminate\Notifications\Notification;

class PortalLeaveApprovalRequiredNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(public LeaveApplication $application) {}
    public function toDatabase(object $notifiable): array
    {
        $employee = $this->application->employee;
        $type = $this->application->leaveType;

        return [
            'type'    => 'leave_approval_required',
            'title'   => 'Leave Approval Required',
            'message' => ($employee?->name ?? 'Employee') . ' applied for '
                . ($type?->name ?? 'leave') . ' (' . $this->application->total_days . ' day(s))',
            'url'     => route('employee.leave'),
        ];
    }
}
