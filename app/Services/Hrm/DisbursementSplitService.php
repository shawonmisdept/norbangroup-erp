<?php

namespace App\Services\Hrm;

use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryStructure;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DisbursementSplitService
{
    /** @return array{bank_pay_amount: float, cash_pay_amount: float} */
    public function splitFromStructure(SalaryStructure $structure, float $netPay): array
    {
        $netPay = round(max(0, $netPay), 2);

        return match ($structure->payment_method) {
            'cash'  => ['bank_pay_amount' => 0.0, 'cash_pay_amount' => $netPay],
            'split' => $this->splitFixedBank($netPay, (float) $structure->bank_disbursement_amount),
            default => ['bank_pay_amount' => $netPay, 'cash_pay_amount' => 0.0],
        };
    }

    /** @return array{bank_pay_amount: float, cash_pay_amount: float} */
    public function splitFixedBank(float $netPay, float $fixedBankAmount): array
    {
        $netPay = round(max(0, $netPay), 2);
        $fixedBankAmount = round(max(0, $fixedBankAmount), 2);
        $bank = min($fixedBankAmount, $netPay);

        return [
            'bank_pay_amount' => $bank,
            'cash_pay_amount' => round($netPay - $bank, 2),
        ];
    }

    public function assertMatchesNet(float $netPay, float $bankPay, float $cashPay): void
    {
        if (abs($netPay - ($bankPay + $cashPay)) > 0.01) {
            throw ValidationException::withMessages([
                'disbursement' => 'Bank and cash amounts must equal net pay.',
            ]);
        }
    }

    public function applyOverride(PayrollItem $item, float $bankPay, float $cashPay): PayrollItem
    {
        if ($item->period?->isFrozen()) {
            throw ValidationException::withMessages([
                'period' => 'Disbursement cannot be changed after the period is closed.',
            ]);
        }

        $bankPay = round(max(0, $bankPay), 2);
        $cashPay = round(max(0, $cashPay), 2);
        $netPay = round((float) $item->net_pay, 2);

        $this->assertMatchesNet($netPay, $bankPay, $cashPay);

        $item->update([
            'bank_pay_amount'       => $bankPay,
            'cash_pay_amount'       => $cashPay,
            'disbursement_override' => true,
            'cash_disbursed_at'     => $cashPay > 0 ? null : $item->cash_disbursed_at,
            'cash_disbursed_by'     => $cashPay > 0 ? null : $item->cash_disbursed_by,
        ]);

        return $item->fresh();
    }

    public function markCashDisbursed(PayrollItem $item, User $user): PayrollItem
    {
        if ($item->period?->isFrozen()) {
            throw ValidationException::withMessages([
                'period' => 'Cash disbursement cannot be marked after the period is closed.',
            ]);
        }

        if ((float) $item->cash_pay_amount <= 0) {
            throw ValidationException::withMessages([
                'cash' => 'This employee has no cash portion for this period.',
            ]);
        }

        $item->update([
            'cash_disbursed_at' => now(),
            'cash_disbursed_by' => $user->id,
        ]);

        return $item->fresh();
    }

    public function markAllCashDisbursed(PayrollPeriod $period, User $user): int
    {
        if ($period->isFrozen()) {
            throw ValidationException::withMessages([
                'period' => 'Cash disbursement cannot be marked after the period is closed.',
            ]);
        }

        return $period->items()
            ->where('cash_pay_amount', '>', 0)
            ->whereNull('cash_disbursed_at')
            ->update([
                'cash_disbursed_at' => now(),
                'cash_disbursed_by' => $user->id,
            ]);
    }

    public function assertReadyToFreeze(PayrollPeriod $period): void
    {
        $pending = $period->items()
            ->where('cash_pay_amount', '>', 0)
            ->whereNull('cash_disbursed_at')
            ->count();

        if ($pending > 0) {
            throw ValidationException::withMessages([
                'disbursement' => "Mark cash disbursed for all {$pending} pending employee(s) before closing the period.",
            ]);
        }
    }

    public function pendingCashCount(PayrollPeriod $period): int
    {
        return $period->items()
            ->where('cash_pay_amount', '>', 0)
            ->whereNull('cash_disbursed_at')
            ->count();
    }

    /** Backfill split amounts for legacy rows missing disbursement data. */
    public function backfillItem(PayrollItem $item, ?SalaryStructure $structure = null): void
    {
        if ((float) $item->bank_pay_amount > 0 || (float) $item->cash_pay_amount > 0) {
            if (abs((float) $item->net_pay - ((float) $item->bank_pay_amount + (float) $item->cash_pay_amount)) <= 0.01) {
                return;
            }
        }

        $structure ??= $item->employee?->salaryStructure;

        if (! $structure) {
            $item->update([
                'bank_pay_amount' => (float) $item->net_pay,
                'cash_pay_amount' => 0,
            ]);

            return;
        }

        $split = $this->splitFromStructure($structure, (float) $item->net_pay);
        $item->update($split);
    }
}
