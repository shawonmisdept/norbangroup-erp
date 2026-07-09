<?php

namespace App\Notifications;

use App\Models\Tms\TmsVehicle;
use App\Support\NotificationUrl;
use Illuminate\Notifications\Notification;

class TmsVehiclePaperAlertNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public TmsVehicle $vehicle,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_vehicle_paper_alert',
            'title'   => $this->title,
            'message' => $this->message,
            'url'     => NotificationUrl::route('admin.tms.vehicles.papers', [], ['vehicle_id' => $this->vehicle->id]),
        ];
    }
}
