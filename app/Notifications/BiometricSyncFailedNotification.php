<?php

namespace App\Notifications;

use App\Models\Hrm\BiometricDevice;
use Illuminate\Notifications\Notification;

class BiometricSyncFailedNotification extends Notification
{
    public function __construct(
        public BiometricDevice $device,
        public string $errorMessage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'hrm_sync_failed',
            'title'   => 'Biometric Sync Failed',
            'message' => ($this->device->name ?? 'Device') . ' — ' . $this->errorMessage,
            'url'     => route('admin.hrm.attendance.sync.failures'),
        ];
    }
}
