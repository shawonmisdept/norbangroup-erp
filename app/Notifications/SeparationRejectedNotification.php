<?php

namespace App\Notifications;

use App\Models\Hrm\EmployeeSeparation;
use Illuminate\Notifications\Notification;

class SeparationRejectedNotification extends Notification
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
            'type'    => 'separation_rejected',
            'title'   => 'Separation request rejected',
            'message' => ($this->separation->employee?->name ?? 'Employee') . ' — ' . $this->separation->typeLabel(),
            'url'     => route('admin.hrm.separations.show', $this->separation),
        ];
    }
}
