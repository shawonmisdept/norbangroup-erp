<?php

namespace App\Notifications;

use App\Models\Hrm\FinalSettlement;
use Illuminate\Notifications\Notification;

class FinalSettlementApprovedNotification extends Notification
{
    public function __construct(public FinalSettlement $settlement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->settlement->loadMissing('employee');

        return [
            'type'    => 'final_settlement_approved',
            'title'   => 'F&F Approved — Pending Disbursement',
            'message' => ($this->settlement->employee?->name ?? 'Employee')
                . ' — ৳' . number_format((float) $this->settlement->net_payable, 2) . ' ready for payment.',
            'url'     => route('admin.hrm.finance.final-settlement.show', $this->settlement),
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
