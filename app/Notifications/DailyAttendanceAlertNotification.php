<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class DailyAttendanceAlertNotification extends Notification
{
    public function __construct(
        public string $factoryName,
        public int $lateCount,
        public int $absentCount,
        public string $dateLabel,
        public ?string $detail = null,
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "{$this->factoryName}: {$this->lateCount} late, {$this->absentCount} absent on {$this->dateLabel}";

        if ($this->detail) {
            $message .= ' — ' . $this->detail;
        }

        return [
            'type'    => 'hrm_daily_attendance',
            'title'   => 'Daily Attendance Alert',
            'message' => $message,
            'url'     => $this->url ?? route('admin.hrm.attendance.reports.index'),
        ];
    }
}
