<?php

namespace App\Notifications;

use App\Models\Hrm\LoanAccount;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use Illuminate\Notifications\Notification;

class PortalLoanSettledNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(public LoanAccount $loan, public float $amount) {}
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'loan_settled',
            'title'   => 'Loan Settled',
            'message' => 'Early settlement of ৳' . number_format($this->amount, 2) . ' recorded. ' . ($this->loan->status === 'closed' ? 'Loan closed.' : 'Balance: ৳' . number_format((float) $this->loan->balance, 2)),
            'url'     => route('employee.loans'),
        ];
    }
}
