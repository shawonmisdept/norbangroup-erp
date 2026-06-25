<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\Employee;
use App\Models\Hrm\FinalSettlement;
use App\Models\Hrm\LeaveBalance;
use App\Models\Hrm\LoanAccount;
use App\Models\Hrm\SalaryStructure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinalSettlementService
{
    public function __construct(
        private GratuityCalculator $gratuityCalculator,
        private LoanRecoveryService $loanRecovery,
    ) {}

    public function createDraft(Employee $employee, User $user, Carbon $lastWorkingDay): FinalSettlement
    {
        if (! in_array($employee->status, ['resigned', 'terminated'], true)) {
            throw new InvalidArgumentException('Final settlement can only be created for resigned or terminated employees.');
        }

        if (FinalSettlement::query()->where('employee_id', $employee->id)->exists()) {
            throw new InvalidArgumentException('A final settlement record already exists for this employee.');
        }

        return FinalSettlement::create([
            'factory_id'        => $employee->factory_id,
            'employee_id'       => $employee->id,
            'separation_type'   => $employee->status,
            'last_working_day'  => $lastWorkingDay,
            'status'            => 'draft',
            'clearance'         => FinalSettlement::defaultClearance(),
        ]);
    }

    public function calculate(FinalSettlement $settlement, User $user): FinalSettlement
    {
        if (! in_array($settlement->status, ['draft', 'calculated'], true)) {
            throw new InvalidArgumentException('Only draft or calculated settlements can be recalculated.');
        }

        $employee = $settlement->employee()->with([
            'pfAccount',
            'loanAccounts',
            'gratuitySettlement',
            'leaveBalances.leaveType',
        ])->firstOrFail();

        $lastDay = Carbon::parse($settlement->last_working_day);
        $structure = $this->activeSalaryStructure($employee);

        $gratuityRecord = $employee->gratuitySettlement
            ?? $this->gratuityCalculator->calculateOnSeparation($employee, $user, $lastDay);

        $gratuityAmount = (float) ($gratuityRecord?->gratuity_amount ?? 0);
        $pfWithdrawal = $this->resolvePfWithdrawal($employee);
        $loanDeduction = $this->resolveLoanDeduction($employee);
        $leaveEncashment = $this->resolveLeaveEncashment($employee, $lastDay, $structure);
        $unpaidSalary = $this->resolveUnpaidSalary($employee, $lastDay, $structure);

        $otherEarnings = (float) $settlement->other_earnings;
        $otherDeductions = (float) $settlement->other_deductions;
        $taxDeduction = (float) $settlement->tax_deduction;

        $breakdown = $this->buildBreakdown(
            $employee,
            $lastDay,
            $unpaidSalary,
            $leaveEncashment,
            $gratuityAmount,
            $pfWithdrawal,
            $loanDeduction,
            $taxDeduction,
            $otherEarnings,
            $otherDeductions
        );

        $netPayable = round(
            $unpaidSalary + $leaveEncashment + $gratuityAmount + $pfWithdrawal + $otherEarnings
            - $loanDeduction - $taxDeduction - $otherDeductions,
            2
        );

        $settlement->update([
            'unpaid_salary'          => $unpaidSalary,
            'leave_encashment'       => $leaveEncashment,
            'gratuity_amount'        => $gratuityAmount,
            'pf_withdrawal'          => $pfWithdrawal,
            'loan_deduction'         => $loanDeduction,
            'net_payable'            => $netPayable,
            'breakdown'              => $breakdown,
            'gratuity_settlement_id' => $gratuityRecord?->id,
            'status'                 => 'calculated',
            'calculated_by'          => $user->id,
            'calculated_at'          => now(),
        ]);

        return $settlement->fresh(['employee', 'gratuitySettlement']);
    }

    public function updateAdjustments(FinalSettlement $settlement, array $data): FinalSettlement
    {
        if (! in_array($settlement->status, ['draft', 'calculated'], true)) {
            throw new InvalidArgumentException('Adjustments can only be edited before approval.');
        }

        $settlement->fill([
            'other_earnings'    => $data['other_earnings'] ?? $settlement->other_earnings,
            'other_deductions'  => $data['other_deductions'] ?? $settlement->other_deductions,
            'tax_deduction'     => $data['tax_deduction'] ?? $settlement->tax_deduction,
            'notes'             => $data['notes'] ?? $settlement->notes,
        ]);

        $netPayable = round(
            (float) $settlement->unpaid_salary
            + (float) $settlement->leave_encashment
            + (float) $settlement->gratuity_amount
            + (float) $settlement->pf_withdrawal
            + (float) $settlement->other_earnings
            - (float) $settlement->loan_deduction
            - (float) $settlement->tax_deduction
            - (float) $settlement->other_deductions,
            2
        );

        $settlement->loadMissing('employee');
        $settlement->net_payable = $netPayable;
        $settlement->breakdown = $this->buildBreakdown(
            $settlement->employee,
            Carbon::parse($settlement->last_working_day),
            (float) $settlement->unpaid_salary,
            (float) $settlement->leave_encashment,
            (float) $settlement->gratuity_amount,
            (float) $settlement->pf_withdrawal,
            (float) $settlement->loan_deduction,
            (float) $settlement->tax_deduction,
            (float) $settlement->other_earnings,
            (float) $settlement->other_deductions
        );
        $settlement->save();

        return $settlement->fresh();
    }

    public function updateClearance(FinalSettlement $settlement, array $clearance): FinalSettlement
    {
        $merged = array_merge(FinalSettlement::defaultClearance(), $clearance);

        foreach (FinalSettlement::CLEARANCE_KEYS as $key => $label) {
            $merged[$key] = ! empty($merged[$key]);
        }

        $settlement->update(['clearance' => $merged]);

        return $settlement->fresh();
    }

    public function approve(FinalSettlement $settlement, User $user): FinalSettlement
    {
        if ($settlement->status !== 'calculated') {
            throw new InvalidArgumentException('Settlement must be calculated before approval.');
        }

        if (! $settlement->clearanceComplete()) {
            throw new InvalidArgumentException('All clearance departments must be checked before approval.');
        }

        $settlement->update([
            'status'      => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $settlement->fresh();
    }

    public function markPaid(FinalSettlement $settlement, User $user): FinalSettlement
    {
        if ($settlement->status !== 'approved') {
            throw new InvalidArgumentException('Settlement must be approved before marking as paid.');
        }

        DB::transaction(function () use ($settlement, $user) {
            $settlement->loadMissing(['gratuitySettlement', 'employee.loanAccounts', 'employee.pfAccount']);
            $employee = $settlement->employee;

            foreach ($employee->loanAccounts()->where('status', 'active')->where('balance', '>', 0)->get() as $loan) {
                $this->loanRecovery->earlySettle(
                    $loan,
                    $user,
                    (float) $loan->balance,
                    'Final settlement #' . $settlement->id
                );
            }

            if ($employee->pfAccount && $employee->pfAccount->is_active) {
                $employee->pfAccount->update([
                    'is_active' => false,
                    'balance'   => 0,
                ]);
            }

            if ($settlement->gratuitySettlement && $settlement->gratuitySettlement->status === 'calculated') {
                $settlement->gratuitySettlement->update([
                    'status'  => 'paid',
                    'paid_at' => now(),
                ]);
            }

            $settlement->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);
        });

        return $settlement->fresh(['employee', 'gratuitySettlement']);
    }

    private function resolvePfWithdrawal(Employee $employee): float
    {
        $account = $employee->pfAccount;

        if (! $account || ! $account->is_active) {
            return 0.0;
        }

        return round((float) $account->balance, 2);
    }

    private function resolveLoanDeduction(Employee $employee): float
    {
        return round(
            (float) $employee->loanAccounts()
                ->where('status', 'active')
                ->sum('balance'),
            2
        );
    }

    /** @return list<array{leave_type: string, days: float, rate: float, amount: float}> */
    private function leaveEncashmentDetails(Employee $employee, Carbon $lastDay, ?SalaryStructure $structure = null): array
    {
        $dailyRate = $this->dailyRate($employee, $structure);
        $details = [];

        LeaveBalance::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $lastDay->year)
            ->get()
            ->each(function (LeaveBalance $balance) use ($dailyRate, &$details) {
                if (! $balance->leaveType?->is_paid) {
                    return;
                }

                $days = $balance->availableDays();

                if ($days <= 0) {
                    return;
                }

                $details[] = [
                    'leave_type' => $balance->leaveType->name,
                    'days'       => $days,
                    'rate'       => $dailyRate,
                    'amount'     => round($days * $dailyRate, 2),
                ];
            });

        return $details;
    }

    private function resolveLeaveEncashment(Employee $employee, Carbon $lastDay, ?SalaryStructure $structure = null): float
    {
        return round(
            collect($this->leaveEncashmentDetails($employee, $lastDay, $structure))->sum('amount'),
            2
        );
    }

    /** @return list<array{id: int, type: string, balance: float}> */
    private function loanDetails(Employee $employee): array
    {
        return $employee->loanAccounts()
            ->where('status', 'active')
            ->where('balance', '>', 0)
            ->get()
            ->map(fn (LoanAccount $loan) => [
                'id'      => $loan->id,
                'type'    => $loan->loanTypeLabel(),
                'balance' => (float) $loan->balance,
            ])
            ->values()
            ->all();
    }

    private function resolveUnpaidSalary(Employee $employee, Carbon $lastDay, ?SalaryStructure $structure = null): float
    {
        $structure ??= $this->activeSalaryStructure($employee);

        if (! $structure) {
            return 0.0;
        }

        if ($structure->pay_type === 'wages') {
            $presentDays = AttendanceDailyLog::query()
                ->where('employee_id', $employee->id)
                ->whereYear('attendance_date', $lastDay->year)
                ->whereMonth('attendance_date', $lastDay->month)
                ->whereDate('attendance_date', '<=', $lastDay->toDateString())
                ->whereIn('status', ['present', 'late', 'half_day'])
                ->count();

            return round((float) $structure->daily_wage * $presentDays, 2);
        }

        $daysWorked = $lastDay->day;
        $monthlyGross = (float) ($structure->gross_salary ?: $structure->basic_salary);

        return round(($monthlyGross / 30) * $daysWorked, 2);
    }

    private function dailyRate(Employee $employee, ?SalaryStructure $structure = null): float
    {
        $structure ??= $this->activeSalaryStructure($employee);

        if (! $structure) {
            return 0.0;
        }

        if ($structure->pay_type === 'wages') {
            return round((float) $structure->daily_wage, 2);
        }

        $monthly = (float) ($structure->gross_salary ?: $structure->basic_salary);

        return round($monthly / 30, 2);
    }

    private function activeSalaryStructure(Employee $employee): ?SalaryStructure
    {
        return SalaryStructure::query()
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->latest('id')
            ->first()
            ?? $employee->salaryStructure;
    }

    /** @return array<string, mixed> */
    private function buildBreakdown(
        Employee $employee,
        Carbon $lastDay,
        float $unpaidSalary,
        float $leaveEncashment,
        float $gratuityAmount,
        float $pfWithdrawal,
        float $loanDeduction,
        float $taxDeduction,
        float $otherEarnings,
        float $otherDeductions,
    ): array {
        $earnings = [
            ['label' => 'Unpaid salary (pro-rata)', 'amount' => $unpaidSalary],
            ['label' => 'Leave encashment', 'amount' => $leaveEncashment],
            ['label' => 'Gratuity', 'amount' => $gratuityAmount],
            ['label' => 'PF withdrawal (employee share)', 'amount' => $pfWithdrawal],
        ];

        if ($otherEarnings > 0) {
            $earnings[] = ['label' => 'Other earnings', 'amount' => $otherEarnings];
        }

        $deductions = [
            ['label' => 'Outstanding loans', 'amount' => $loanDeduction],
            ['label' => 'Tax / TDS adjustment', 'amount' => $taxDeduction],
        ];

        if ($otherDeductions > 0) {
            $deductions[] = ['label' => 'Other deductions', 'amount' => $otherDeductions];
        }

        $structure = $this->activeSalaryStructure($employee);

        return [
            'earnings'      => $earnings,
            'deductions'    => $deductions,
            'leave_details' => $this->leaveEncashmentDetails($employee, $lastDay, $structure),
            'loan_details'  => $this->loanDetails($employee),
        ];
    }
}
