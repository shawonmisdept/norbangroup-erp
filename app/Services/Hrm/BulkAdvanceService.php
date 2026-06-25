<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\LoanAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BulkAdvanceService
{
    public function __construct(
        private LoanRecoveryService $recovery,
        private HrmNotificationService $notifier,
    ) {}

    /**
     * @param  array<int, float>  $amounts  employee_id => advance amount
     * @return array{created: int, approved: int, skipped: int, errors: list<string>}
     */
    public function disburse(
        int $factoryId,
        User $user,
        array $amounts,
        int $totalInstallments,
        bool $autoApprove,
        ?string $notes = null
    ): array {
        $result = ['created' => 0, 'approved' => 0, 'skipped' => 0, 'errors' => []];
        $notifyLoanIds = [];

        DB::transaction(function () use ($factoryId, $user, $amounts, $totalInstallments, $autoApprove, $notes, &$result, &$notifyLoanIds) {
            foreach ($amounts as $employeeId => $principal) {
                $principal = round((float) $principal, 2);

                if ($principal <= 0) {
                    continue;
                }

                $employee = Employee::query()
                    ->where('id', $employeeId)
                    ->where('factory_id', $factoryId)
                    ->whereIn('status', ['active', 'probation'])
                    ->first();

                if (! $employee) {
                    $result['skipped']++;
                    $result['errors'][] = "Employee #{$employeeId} not found or inactive.";

                    continue;
                }

                $hasOpenLoan = LoanAccount::query()
                    ->where('employee_id', $employee->id)
                    ->whereIn('status', ['pending', 'active'])
                    ->exists();

                if ($hasOpenLoan) {
                    $result['skipped']++;
                    $result['errors'][] = "{$employee->employee_code}: already has an open advance/loan.";

                    continue;
                }

                $emi = LoanAccount::calculateEmi($principal, $totalInstallments);

                $loan = LoanAccount::create([
                    'factory_id'         => $factoryId,
                    'employee_id'        => $employee->id,
                    'loan_type'          => 'advance',
                    'principal'          => $principal,
                    'balance'            => $principal,
                    'emi_amount'         => $emi,
                    'total_installments' => $totalInstallments,
                    'paid_installments'  => 0,
                    'status'             => $autoApprove ? 'active' : 'pending',
                    'notes'              => $notes,
                    'approved_by'        => $autoApprove ? $user->id : null,
                    'approved_at'        => $autoApprove ? now() : null,
                ]);

                $result['created']++;

                if ($autoApprove) {
                    foreach ($this->recovery->buildSchedule($loan) as $installment) {
                        $installment->save();
                    }
                    $result['approved']++;
                    $notifyLoanIds[] = $loan->id;
                }
            }
        });

        foreach ($notifyLoanIds as $loanId) {
            $loan = LoanAccount::with('employee.portalUser')->find($loanId);
            if ($loan) {
                $this->notifier->advanceDisbursed($loan);
            }
        }

        return $result;
    }
}
