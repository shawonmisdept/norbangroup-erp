<?php

namespace App\Notifications;

use App\Models\Hrm\EmployeeSeparation;
use Illuminate\Notifications\Notification;

class SeparationPendingHrNotification extends Notification
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
            'type'    => 'separation_pending_hr',
            'title'   => 'Separation awaiting HR approval',
            'message' => ($this->separation->employee?->name ?? 'Employee') . ' — ' . $this->separation->typeLabel(),
            'url'     => route('admin.hrm.separations.show', $this->separation),
        ];
    }
}
