<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use App\Support\NotificationUrl;
use Illuminate\Notifications\Notification;

class PortalTmsRequestRejectedNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(public TmsTransportRequest $request) {}
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_request_rejected',
            'title'   => 'Transport Request Rejected',
            'message' => 'Your transport request was rejected: ' . ($this->request->rejection_reason ?? 'No reason given'),
            'url'     => NotificationUrl::route('employee.transport.requests.show', $this->request),
        ];
    }
}
