<?php

namespace App\Jobs\Hrm;

use App\Mail\PayslipReadyMail;
use App\Models\AppSetting;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Services\Hrm\HrmNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPeriodPayslipsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $payrollPeriodId,
    ) {
        $this->onQueue(config('hrm.queues.mail', 'hrm-mail'));
    }

    public function handle(HrmNotificationService $notifications): void
    {
        $period = PayrollPeriod::findOrFail($this->payrollPeriodId);

        if (! $period->isFrozen()) {
            return;
        }

        $settings = AppSetting::current();
        $sent = 0;

        PayrollItem::query()
            ->with(['employee.portalUser', 'period'])
            ->where('payroll_period_id', $period->id)
            ->where('net_pay', '>', 0)
            ->chunkById(50, function ($items) use (&$sent, $settings, $notifications) {
                foreach ($items as $item) {
                    $email = $item->employee?->email;

                    if ($settings->notify_mail_hrm_payslip && $email && $settings->canSendMail()) {
                        try {
                            Mail::to($email)->send(new PayslipReadyMail($item));
                        } catch (\Throwable) {
                            // continue sending others
                        }
                    }

                    $notifications->payslipReady($item);
                    $item->update(['payslip_sent_at' => now()]);
                    $sent++;
                }
            });

        if ($sent > 0) {
            $period->update(['payslips_sent_at' => now()]);
        }
    }
}
