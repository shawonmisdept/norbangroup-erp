<?php

namespace App\Notifications;

use App\Models\Hrm\EmployeePromotion;
use Illuminate\Notifications\Notification;

class PromotionApprovedNotification extends Notification
{
    public function __construct(public EmployeePromotion $promotion) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->promotion->loadMissing('employee');

        return [
            'type'    => 'promotion_approved',
            'title'   => 'Promotion / Demotion Approved',
            'message' => ($this->promotion->employee?->name ?? 'Employee') . ' — '
                . $this->promotion->movementTypeLabel() . ' approved',
            'url'     => route('admin.hrm.promotions.show', $this->promotion),
        ];
    }
}
