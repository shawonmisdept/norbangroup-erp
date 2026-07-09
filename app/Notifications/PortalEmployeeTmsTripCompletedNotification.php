<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use App\Support\NotificationUrl;
use Illuminate\Notifications\Notification;

class PortalEmployeeTmsTripCompletedNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(
        public TmsTransportRequest $request,
        public bool $forDriver = false,
    ) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_trip_completed',
            'title'   => 'Trip Completed',
            'message' => $this->forDriver
                ? 'Your assigned trip has been completed.'
                : 'Your transport trip has been completed.',
            'url'     => $this->forDriver
                ? NotificationUrl::route('employee.transport.trips')
                : NotificationUrl::route('employee.transport.requests.show', $this->request),
        ];
    }
}
