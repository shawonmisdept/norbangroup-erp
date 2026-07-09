<?php

namespace App\Notifications;

use App\Models\Tms\TmsVehicle;
use App\Support\NotificationUrl;
use Carbon\Carbon;
use Illuminate\Notifications\Notification;

class TmsOdometerReminderNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public TmsVehicle $vehicle,
        public string $date,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_odometer_reminder',
            'title'   => $this->title,
            'message' => $this->message,
            'url'     => NotificationUrl::route('admin.tms.odometer.index', [], [
                'factory_id' => $this->vehicle->factory_id,
                'date'       => Carbon::parse($this->date)->toDateString(),
            ]),
        ];
    }
}
