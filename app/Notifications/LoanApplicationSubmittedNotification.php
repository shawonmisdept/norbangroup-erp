<?php

namespace App\Notifications;

use App\Models\Hrm\LoanAccount;
use Illuminate\Notifications\Notification;

class LoanApplicationSubmittedNotification extends Notification
{
    public function __construct(public LoanAccount $loan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->loan->loadMissing('employee');

        return [
            'type'    => 'loan_application',
            'title'   => 'Loan Application',
            'message' => ($this->loan->employee?->name ?? 'Employee') . ' applied for ' . $this->loan->loanTypeLabel() . ' — ৳' . number_format((float) $this->loan->principal, 2),
            'url'     => route('admin.hrm.finance.loans.show', $this->loan),
        ];
    }
}
