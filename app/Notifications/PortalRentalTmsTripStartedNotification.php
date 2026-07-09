<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use App\Notifications\Concerns\DeliversRentalWebPush;
use App\Support\NotificationUrl;
use App\Support\PortalDateTime;
use Illuminate\Notifications\Notification;

class PortalRentalTmsTripStartedNotification extends Notification
{
    use DeliversRentalWebPush;

    public function __construct(public TmsTransportRequest $request) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_trip_started',
            'title'   => 'Trip Started',
            'message' => 'Your assigned trip has started for ' . PortalDateTime::dateTime($this->request->pickup_at) . '.',
            'url'     => NotificationUrl::route('rental.trips'),
        ];
    }
}
