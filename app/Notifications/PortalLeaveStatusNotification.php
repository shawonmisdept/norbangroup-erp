<?php

namespace App\Notifications;

use App\Models\Hrm\LeaveApplication;
use Illuminate\Notifications\Notification;

class PortalLeaveStatusNotification extends Notification
{
    public function __construct(
        public LeaveApplication $application,
        public string $statusLabel,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

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
