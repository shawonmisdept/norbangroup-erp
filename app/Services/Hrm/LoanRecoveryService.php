<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\LoanAccount;
use App\Models\Hrm\LoanInstallment;
use App\Models\Hrm\PayrollPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanRecoveryService
{
    public function dueRecovery(Employee $employee, PayrollPeriod $period): array
    {
        $dueDate = Carbon::create($period->year, $period->month, 1)->endOfMonth();

        $loan = LoanAccount::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where('balance', '>', 0)
            ->orderBy('id')
            ->first();

        if (! $loan) {
            return ['loan_account_id' => null, 'installment_id' => null, 'amount' => 0.0];
        }

        $installment = LoanInstallment::query()
            ->where('loan_account_id', $loan->id)
            ->where('status', 'pending')
            ->whereDate('due_date', '<=', $dueDate)
            ->orderBy('installment_no')
            ->first();

        if (! $installment) {
            $amount = min((float) $loan->emi_amount, (float) $loan->balance);

            return [
                'loan_account_id' => $loan->id,
                'installment_id'  => null,
                'amount'          => round($amount, 2),
            ];
        }

        return [
            'loan_account_id' => $loan->id,
            'installment_id'  => $installment->id,
            'amount'          => min((float) $installment->amount, (float) $loan->balance),
        ];
    }

    public function recordRecovery(
        LoanAccount $loan,
        ?LoanInstallment $installment,
        float $amount,
        PayrollPeriod $period
    ): void {
        if ($amount <= 0) {
            return;
        }

        if ($installment) {
            $installment->update([
                'status'            => 'paid',
                'payroll_period_id' => $period->id,
            ]);
        } else {
            LoanInstallment::create([
                'loan_account_id'   => $loan->id,
                'payroll_period_id' => $period->id,
                'installment_no'    => $loan->paid_installments + 1,
                'due_date'          => $period->end_date,
                'amount'            => $amount,
                'status'            => 'paid',
            ]);
        }

        $loan->balance = max(0, round((float) $loan->balance - $amount, 2));
        $loan->paid_installments = $loan->paid_installments + 1;

        if ($loan->balance <= 0) {
            $loan->status = 'closed';
            $loan->balance = 0;
        }

        $loan->save();
    }

    public function earlySettle(LoanAccount $loan, User $user, ?float $amount = null, ?string $notes = null): void
    {
        if ($loan->status !== 'active' || (float) $loan->balance <= 0) {
            throw new \InvalidArgumentException('Only active loans with a balance can be settled early.');
        }

        $amount = round($amount ?? (float) $loan->balance, 2);

        if ($amount <= 0 || $amount > (float) $loan->balance) {
            throw new \InvalidArgumentException('Settlement amount must be between 0 and the outstanding balance.');
        }

        DB::transaction(function () use ($loan, $user, $amount, $notes) {
            $loan->installments()->where('status', 'pending')->update(['status' => 'waived']);

            $nextNo = (int) ($loan->installments()->max('installment_no') ?? 0) + 1;

            LoanInstallment::create([
                'loan_account_id' => $loan->id,
                'installment_no'  => $nextNo,
                'due_date'        => now()->toDateString(),
                'amount'          => $amount,
                'status'          => 'paid',
            ]);

            $loan->balance = max(0, round((float) $loan->balance - $amount, 2));
            $loan->paid_installments = $loan->paid_installments + 1;

            if ($loan->balance <= 0) {
                $loan->status = 'closed';
                $loan->balance = 0;
            }

            $noteLine = '[Early settlement ' . now()->format('d M Y') . ' by ' . $user->name . '] ৳' . number_format($amount, 2);
            if ($notes) {
                $noteLine .= ' — ' . $notes;
            }

            $loan->notes = trim(($loan->notes ? $loan->notes . "\n" : '') . $noteLine);
            $loan->save();
        });
    }

    /** @return list<LoanInstallment> */
    public function buildSchedule(LoanAccount $loan): array
    {
        $installments = [];
        $remaining = (float) $loan->principal;
        $emi = (float) $loan->emi_amount;
        $due = ($loan->approved_at ?? now())->copy()->startOfMonth();

        for ($i = 1; $i <= $loan->total_installments; $i++) {
            $amount = $i === $loan->total_installments ? $remaining : min($emi, $remaining);
            $installments[] = new LoanInstallment([
                'loan_account_id' => $loan->id,
                'installment_no'  => $i,
                'due_date'        => $due->copy()->addMonths($i - 1),
                'amount'          => round($amount, 2),
                'status'          => 'pending',
            ]);
            $remaining -= $amount;
        }

        return $installments;
    }
}
