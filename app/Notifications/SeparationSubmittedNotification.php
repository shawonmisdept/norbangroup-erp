<?php

namespace App\Notifications;

use App\Models\Hrm\EmployeeSeparation;
use Illuminate\Notifications\Notification;

class SeparationSubmittedNotification extends Notification
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
            'type'    => 'separation_submitted',
            'title'   => 'Separation request submitted',
            'message' => ($this->separation->employee?->name ?? 'Employee') . ' — ' . $this->separation->typeLabel(),
            'url'     => route('admin.hrm.separations.show', $this->separation),
        ];
    }
}
