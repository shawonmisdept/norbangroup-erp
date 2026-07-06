<?php

namespace App\Notifications;

use App\Models\Hrm\EmployeePromotion;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use Illuminate\Notifications\Notification;

class PortalPromotionStatusNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(
        public EmployeePromotion $promotion,
        public string $statusLabel,
    ) {}
    public function toArray(object $notifiable): array
    {
        $this->promotion->loadMissing('toDesignation');

        return [
            'type'    => 'promotion_status',
            'title'   => $this->promotion->movementTypeLabel() . ' ' . $this->statusLabel,
            'message' => 'Your ' . strtolower($this->promotion->movementTypeLabel()) . ' request'
                . ($this->statusLabel === 'Approved' && $this->promotion->toDesignation
                    ? ' to ' . $this->promotion->toDesignation->name
                    : '')
                . ' has been ' . strtolower($this->statusLabel) . '.',
            'url'     => route('employee.career.promotions.show', $this->promotion),
        ];
    }
}
