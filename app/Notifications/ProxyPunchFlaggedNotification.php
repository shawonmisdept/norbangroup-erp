<?php

namespace App\Notifications;

use App\Models\Hrm\ProxyPunchFlag;
use Illuminate\Notifications\Notification;

class ProxyPunchFlaggedNotification extends Notification
{
    public function __construct(public ProxyPunchFlag $flag) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->flag->loadMissing('employee');

        return [
            'type'    => 'proxy_punch_flagged',
            'title'   => 'Proxy Punch Flagged',
            'message' => 'Suspicious punch flagged for ' . ($this->flag->employee?->name ?? 'unknown employee'),
            'url'     => route('admin.hrm.rmg.proxy-punch.index', ['status' => 'open']),
        ];
    }
}
