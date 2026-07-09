<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use App\Support\NotificationUrl;
use App\Support\PortalDateTime;
use Illuminate\Notifications\Notification;

class TmsTripStartedNotification extends Notification
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
            'type'    => 'tms_trip_started',
            'title'   => 'Trip Started',
            'message' => 'Driver started the trip for ' . ($employee?->name ?? 'employee') . ' — '
                . PortalDateTime::dateTime($this->request->pickup_at),
            'url'     => NotificationUrl::route('admin.tms.requests.show', $this->request),
        ];
    }
}
