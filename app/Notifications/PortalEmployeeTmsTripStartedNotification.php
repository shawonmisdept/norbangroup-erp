<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use App\Support\NotificationUrl;
use App\Support\PortalDateTime;
use Illuminate\Notifications\Notification;

class PortalEmployeeTmsTripStartedNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(
        public TmsTransportRequest $request,
        public bool $forDriver = false,
    ) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_trip_started',
            'title'   => 'Trip Started',
            'message' => $this->forDriver
                ? 'Your assigned trip has started for ' . PortalDateTime::dateTime($this->request->pickup_at) . '.'
                : 'Your transport trip has started for ' . PortalDateTime::dateTime($this->request->pickup_at) . '.',
            'url'     => $this->forDriver
                ? NotificationUrl::route('employee.transport.trips')
                : NotificationUrl::route('employee.transport.requests.show', $this->request),
        ];
    }
}
