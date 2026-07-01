<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use App\Notifications\Concerns\DeliversRentalWebPush;
use Illuminate\Notifications\Notification;

class PortalTmsRentalDriverTripAssignedNotification extends Notification
{
    use DeliversRentalWebPush;

    public function __construct(public TmsTransportRequest $request) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_trip_assigned',
            'title'   => 'New Trip Assigned',
            'message' => 'You have been assigned a trip on ' . $this->request->pickup_at->format('d M Y H:i'),
            'url'     => route('rental.trips'),
        ];
    }
}
