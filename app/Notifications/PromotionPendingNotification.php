<?php

namespace App\Notifications;

use App\Models\Hrm\EmployeePromotion;
use Illuminate\Notifications\Notification;

class PromotionPendingNotification extends Notification
{
    public function __construct(public EmployeePromotion $promotion) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->promotion->loadMissing(['employee', 'toDesignation']);

        return [
            'type'    => 'promotion_pending',
            'title'   => 'Promotion / Demotion Pending',
            'message' => ($this->promotion->employee?->name ?? 'Employee') . ' — '
                . $this->promotion->movementTypeLabel()
                . ' to ' . ($this->promotion->toDesignation?->name ?? 'new designation')
                . ', effective ' . $this->promotion->effective_date?->format('d M Y'),
            'url'     => route('admin.hrm.promotions.show', $this->promotion),
        ];
    }
}
