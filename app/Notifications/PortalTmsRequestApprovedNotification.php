<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use App\Support\NotificationUrl;
use App\Support\PortalDateTime;
use Illuminate\Notifications\Notification;

class PortalTmsRequestApprovedNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(public TmsTransportRequest $request) {}
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_request_approved',
            'title'   => 'Transport Request Approved',
            'message' => 'Your transport request for ' . PortalDateTime::dateTime($this->request->pickup_at) . ' has been approved.',
            'url'     => NotificationUrl::route('employee.transport.requests.show', $this->request),
        ];
    }
}
