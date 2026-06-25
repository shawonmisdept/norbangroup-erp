<?php

namespace App\Notifications;

use App\Models\Hrm\FinalSettlement;
use Illuminate\Notifications\Notification;

class PortalFinalSettlementPaidNotification extends Notification
{
    public function __construct(public FinalSettlement $settlement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'final_settlement_paid',
            'title'   => 'Final Settlement Processed',
            'message' => 'Your full & final settlement of ৳' . number_format((float) $this->settlement->net_payable, 2) . ' has been disbursed.',
            'url'     => route('employee.notifications.index'),
        ];
    }
}
