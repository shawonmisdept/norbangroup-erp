<?php

namespace App\Notifications;

use App\Models\Hrm\AttendanceRawPunch;
use App\Support\PortalDateTime;
use Illuminate\Notifications\Notification;

class ManualPunchRecordedNotification extends Notification
{
    public function __construct(public AttendanceRawPunch $punch) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $employee = $this->punch->employee;

        return [
            'type'    => 'hrm_manual_punch',
            'title'   => 'Manual Punch Recorded',
            'message' => strtoupper($this->punch->punch_type) . ' for '
                . ($employee?->employee_code ?? 'employee') . ' at '
                . PortalDateTime::dateTime($this->punch->punched_at),
            'url'     => route('admin.hrm.attendance.manual-punch.index'),
        ];
    }
}
