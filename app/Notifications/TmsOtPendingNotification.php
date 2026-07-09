<?php

namespace App\Notifications;

use App\Models\Tms\TmsTripLog;
use App\Support\NotificationUrl;
use Illuminate\Notifications\Notification;

class TmsOtPendingNotification extends Notification
{
    public function __construct(
        public string $driverName,
        public TmsTripLog $tripLog,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_ot_pending',
            'title'   => 'Driver OT Pending Payment',
            'message' => $this->driverName . ' has ৳' . number_format((float) $this->tripLog->ot_amount, 2) . ' OT pending.',
            'url'     => NotificationUrl::route('admin.tms.trips.show', $this->tripLog),
        ];
    }
}
