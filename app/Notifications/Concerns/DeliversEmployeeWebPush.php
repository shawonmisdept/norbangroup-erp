<?php

namespace App\Notifications\Concerns;

use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

trait DeliversEmployeeWebPush
{
    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, mixed $notification): WebPushMessage
    {
        $data = $this->toDatabase($notifiable);

        return (new WebPushMessage)
            ->title($data['title'] ?? config('portal.name', 'Employee Portal'))
            ->body($data['message'] ?? '')
            ->icon(url('/pwa/icon-192.png'))
            ->badge(url('/pwa/icon-192.png'))
            ->data(['url' => $data['url'] ?? url('/employee/dashboard')]);
    }
}
