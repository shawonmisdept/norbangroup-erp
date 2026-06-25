<?php

namespace App\Notifications;

use App\Models\Hrm\EmployeePromotion;
use Illuminate\Notifications\Notification;

class PromotionRejectedNotification extends Notification
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
            'type'    => 'promotion_rejected',
            'title'   => 'Promotion / Demotion Rejected',
            'message' => ($this->promotion->employee?->name ?? 'Employee') . ' — '
                . $this->promotion->movementTypeLabel() . ' rejected',
            'url'     => route('admin.hrm.promotions.show', $this->promotion),
        ];
    }
}
