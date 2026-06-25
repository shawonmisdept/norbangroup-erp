<?php

namespace App\Notifications;

use App\Models\Hrm\LateAcceptanceApplication;
use Illuminate\Notifications\Notification;

class LateAcceptanceAppliedNotification extends Notification
{
    public function __construct(public LateAcceptanceApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $employee = $this->application->employee;

        return [
            'type'    => 'hrm_late_acceptance',
            'title'   => 'Late Acceptance Application',
            'message' => ($employee?->name ?? 'Employee') . ' applied for late forgiveness on '
                . $this->application->attendance_date->format('d M Y'),
            'url'     => route('admin.hrm.attendance.late-acceptance.show', $this->application),
        ];
    }
}
