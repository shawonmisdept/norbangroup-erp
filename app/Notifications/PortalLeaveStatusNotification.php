<?php

namespace App\Notifications;

use App\Models\Hrm\LeaveApplication;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use Illuminate\Notifications\Notification;

class PortalLeaveStatusNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(
        public LeaveApplication $application,
        public string $statusLabel,
    ) {}
    public function toDatabase(object $notifiable): array
    {
        $type = $this->application->leaveType;

        return [
            'type'    => 'leave_status',
            'title'   => 'Leave ' . $this->statusLabel,
            'message' => 'Your ' . ($type?->name ?? 'leave') . ' request (' . $this->application->total_days
                . ' day(s)) was ' . strtolower($this->statusLabel),
            'url'     => route('employee.leave'),
        ];
    }
}
