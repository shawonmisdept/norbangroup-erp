<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class RequirementStatusNotification extends Notification
{
    public function __construct(
        public Order $order,
        public string $previousStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'status_updated',
            'title'           => 'Status Updated',
            'message'         => "{$this->order->ref_code} changed from {$this->previousStatus} to {$this->order->status}",
            'order_id'        => $this->order->id,
            'ref_code'        => $this->order->ref_code,
            'previous_status' => $this->previousStatus,
            'new_status'      => $this->order->status,
            'url'             => route('admin.requirements.show', $this->order),
        ];
    }
}
