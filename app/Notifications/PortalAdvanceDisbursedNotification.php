<?php

namespace App\Notifications;

use App\Models\Hrm\LoanAccount;
use Illuminate\Notifications\Notification;

class PortalAdvanceDisbursedNotification extends Notification
{
    public function __construct(public LoanAccount $loan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'advance_disbursed',
            'title'   => 'Salary Advance Disbursed',
            'message' => $this->loan->loanTypeLabel() . ' of ৳' . number_format((float) $this->loan->principal, 2) . ' approved — EMI ৳' . number_format((float) $this->loan->emi_amount, 2),
            'url'     => route('employee.loans'),
        ];
    }
}
