<?php

namespace App\Notifications;

use App\Models\Hrm\EmployeeSeparation;
use Illuminate\Notifications\Notification;

class SeparationApprovedNotification extends Notification
{
    public function __construct(public EmployeeSeparation $separation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->separation->loadMissing('employee');

        return [
            'type'    => 'separation_approved',
            'title'   => 'Separation approved',
            'message' => ($this->separation->employee?->name ?? 'Employee') . ' — last day ' . $this->separation->last_working_day->format('d M Y'),
            'url'     => route('admin.hrm.separations.show', $this->separation),
        ];
    }
}
