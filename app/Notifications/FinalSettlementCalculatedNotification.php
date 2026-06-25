<?php

namespace App\Notifications;

use App\Models\Hrm\FinalSettlement;
use Illuminate\Notifications\Notification;

class FinalSettlementCalculatedNotification extends Notification
{
    public function __construct(public FinalSettlement $settlement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->settlement->loadMissing('employee');

        return [
            'type'    => 'final_settlement_calculated',
            'title'   => 'F&F Calculated — Clearance Required',
            'message' => ($this->settlement->employee?->name ?? 'Employee') . ' — net payable ৳'
                . number_format((float) $this->settlement->net_payable, 2) . '. Complete clearance checklist.',
            'url'     => route('admin.hrm.finance.final-settlement.show', $this->settlement),
        ];
    }
}
