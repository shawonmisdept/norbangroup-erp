<?php

namespace App\Notifications;

use App\Models\Hrm\LoanAccount;
use Illuminate\Notifications\Notification;

class PortalLoanRejectedNotification extends Notification
{
    public function __construct(public LoanAccount $loan, public ?string $reason = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = $this->loan->loanTypeLabel() . ' application of ৳' . number_format((float) $this->loan->principal, 2) . ' was rejected.';
        if ($this->reason) {
            $message .= ' Reason: ' . $this->reason;
        }

        return [
            'type'    => 'loan_rejected',
            'title'   => 'Loan Application Rejected',
            'message' => $message,
            'url'     => route('employee.loans'),
        ];
    }
}
