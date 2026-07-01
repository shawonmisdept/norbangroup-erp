<?php

namespace App\Jobs\Hrm;

use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\PayrollRun;
use App\Models\User;
use App\Services\Hrm\PayrollProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPayrollJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $payrollPeriodId,
        public int $userId,
    ) {
        $this->onQueue(config('hrm.queues.payroll', 'hrm-payroll'));
    }

    public function handle(PayrollProcessor $processor): void
    {
        $period = PayrollPeriod::findOrFail($this->payrollPeriodId);
        $user = User::findOrFail($this->userId);

        $processor->calculatePeriod($period, $user);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Payroll job failed', [
            'payroll_period_id' => $this->payrollPeriodId,
            'message'           => $exception?->getMessage(),
        ]);

        $period = PayrollPeriod::find($this->payrollPeriodId);

        if (! $period) {
            return;
        }

        PayrollRun::where('payroll_period_id', $period->id)
            ->where('status', 'running')
            ->update([
                'status'       => 'failed',
                'completed_at' => now(),
                'notes'        => $exception?->getMessage(),
            ]);
    }
}
