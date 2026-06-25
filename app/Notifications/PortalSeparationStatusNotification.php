<?php

namespace App\Notifications;

use App\Models\Hrm\EmployeeSeparation;
use Illuminate\Notifications\Notification;

class PortalSeparationStatusNotification extends Notification
{
    public function __construct(
        public EmployeeSeparation $separation,
        public string $statusLabel,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'separation_status',
            'title'   => 'Resignation ' . $this->statusLabel,
            'message' => 'Your separation request has been ' . strtolower($this->statusLabel) . '.',
            'url'     => route('employee.separation'),
        ];
    }
}
