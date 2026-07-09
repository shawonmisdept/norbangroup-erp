<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use App\Notifications\Concerns\DeliversRentalWebPush;
use App\Support\NotificationUrl;
use Illuminate\Notifications\Notification;

class PortalRentalTmsTripCompletedNotification extends Notification
{
    use DeliversRentalWebPush;

    public function __construct(public TmsTransportRequest $request) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_trip_completed',
            'title'   => 'Trip Completed',
            'message' => 'Your assigned trip has been completed.',
            'url'     => NotificationUrl::route('rental.trips'),
        ];
    }
}
