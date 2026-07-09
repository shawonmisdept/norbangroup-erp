<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use App\Support\NotificationUrl;
use Illuminate\Notifications\Notification;

class TmsRequestCancelledNotification extends Notification
{
    public function __construct(public TmsTransportRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $employee = $this->request->employee;

        return [
            'type'    => 'tms_request_cancelled',
            'title'   => 'Transport Request Cancelled',
            'message' => ($employee?->name ?? 'Employee') . ' cancelled their transport request.',
            'url'     => NotificationUrl::route('admin.tms.requests.index'),
        ];
    }
}
