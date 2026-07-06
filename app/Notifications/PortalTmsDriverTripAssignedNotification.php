<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use App\Support\PortalDateTime;
use Illuminate\Notifications\Notification;

class PortalTmsDriverTripAssignedNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(public TmsTransportRequest $request) {}
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_trip_assigned',
            'title'   => 'New Trip Assigned',
            'message' => 'You have been assigned a trip on ' . PortalDateTime::dateTime($this->request->pickup_at),
            'url'     => route('employee.transport.trips'),
        ];
    }
}
