<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class NewRequirementNotification extends Notification
{
    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'     => 'new_requirement',
            'title'    => 'New Requirement',
            'message'  => "{$this->order->ref_code} submitted by {$this->order->name}",
            'order_id' => $this->order->id,
            'ref_code' => $this->order->ref_code,
            'url'      => route('admin.requirements.show', $this->order),
        ];
    }
}
