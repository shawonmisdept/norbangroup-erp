<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Notifications\Notification;

class RequirementAssignedNotification extends Notification
{
    public function __construct(
        public Order $order,
        public ?int $previousAssigneeId = null,
        public ?User $assignedBy = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->order->loadMissing('assignedTo');

        $assigneeName = $this->order->assignedTo?->name ?? 'Unassigned';
        $isAssignee = (int) $this->order->assigned_to_user_id === (int) $notifiable->id;
        $assignedByName = $this->assignedBy?->name;

        $message = $isAssignee
            ? "You were assigned to {$this->order->ref_code} ({$this->order->item_name})"
            : "{$this->order->ref_code} assigned to {$assigneeName}";

        if ($assignedByName && ! $isAssignee) {
            $message .= " by {$assignedByName}";
        }

        return [
            'type'                  => 'requirement_assigned',
            'title'                 => $isAssignee ? 'Requirement Assigned to You' : 'Requirement Assignment Updated',
            'message'               => $message,
            'order_id'              => $this->order->id,
            'ref_code'              => $this->order->ref_code,
            'previous_assignee_id'  => $this->previousAssigneeId,
            'assigned_to_user_id'   => $this->order->assigned_to_user_id,
            'url'                   => route('admin.requirements.show', $this->order),
        ];
    }
}
