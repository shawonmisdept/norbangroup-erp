<?php

namespace App\Notifications;

use App\Models\Hrm\PayrollItem;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use Illuminate\Notifications\Notification;

class PortalPayslipReadyNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(public PayrollItem $payslip) {}
    public function toDatabase(object $notifiable): array
    {
        $label = $this->payslip->period?->periodLabel() ?? 'Payroll';

        return [
            'type'    => 'payslip_ready',
            'title'   => 'Payslip Ready',
            'message' => 'Your payslip for ' . $label . ' is available to download',
            'url'     => route('employee.payslips.show', $this->payslip),
        ];
    }
}
