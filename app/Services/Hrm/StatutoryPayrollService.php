<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeeTaxLedger;
use App\Models\Hrm\PfContribution;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryStructure;
use Carbon\Carbon;

class StatutoryPayrollService
{
    public function __construct(
        private TdsCalculator $tds,
        private PfContributionCalculator $pf,
        private LoanRecoveryService $loans,
    ) {}

    public function apply(
        Employee $employee,
        PayrollPeriod $period,
        SalaryStructure $structure,
        float $grossPay,
        float $basicAmount
    ): array {
        $date = Carbon::create($period->year, $period->month, 15);

        $tdsResult = $this->tds->monthlyTds($employee, $grossPay, $basicAmount, $structure, $date);
        $pfResult = $this->pf->calculate($employee, $basicAmount);
        $loanResult = $this->loans->dueRecovery($employee, $period);

        $statutoryTotal = round(
            $tdsResult['tds_amount'] + $pfResult['employee_amount'] + $loanResult['amount'],
            2
        );

        return [
            'tds_amount'          => $tdsResult['tds_amount'],
            'taxable_income'      => $tdsResult['taxable_income'],
            'tax_year_id'         => $tdsResult['tax_year_id'],
            'pf_employee_amount'  => $pfResult['employee_amount'],
            'pf_employer_amount'  => $pfResult['employer_amount'],
            'pf_account_id'       => $pfResult['pf_account_id'],
            'pf_base_amount'      => $pfResult['base_amount'],
            'loan_deduction'      => $loanResult['amount'],
            'loan_account_id'     => $loanResult['loan_account_id'],
            'loan_installment_id' => $loanResult['installment_id'],
            'statutory_total'     => $statutoryTotal,
        ];
    }

    public function persistLedgers(Employee $employee, PayrollPeriod $period, array $statutory): void
    {
        if ($statutory['tds_amount'] > 0 || $statutory['taxable_income'] > 0) {
            EmployeeTaxLedger::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'year'        => $period->year,
                    'month'       => $period->month,
                ],
                [
                    'factory_id'        => $employee->factory_id,
                    'tax_year_id'       => $statutory['tax_year_id'],
                    'payroll_period_id' => $period->id,
                    'taxable_income'    => $statutory['taxable_income'],
                    'tds_amount'        => $statutory['tds_amount'],
                ]
            );
        }

        if ($statutory['pf_account_id'] && ($statutory['pf_employee_amount'] > 0 || $statutory['pf_employer_amount'] > 0)) {
            PfContribution::updateOrCreate(
                [
                    'pf_account_id' => $statutory['pf_account_id'],
                    'year'          => $period->year,
                    'month'         => $period->month,
                ],
                [
                    'payroll_period_id' => $period->id,
                    'base_amount'       => $statutory['pf_base_amount'],
                    'employee_amount'   => $statutory['pf_employee_amount'],
                    'employer_amount'   => $statutory['pf_employer_amount'],
                ]
            );

            if ($statutory['pf_employee_amount'] > 0) {
                $account = \App\Models\Hrm\PfAccount::find($statutory['pf_account_id']);
                $account?->increment('balance', $statutory['pf_employee_amount']);
            }
        }

        if ($statutory['loan_account_id'] && $statutory['loan_deduction'] > 0) {
            $loan = \App\Models\Hrm\LoanAccount::find($statutory['loan_account_id']);
            $installment = $statutory['loan_installment_id']
                ? \App\Models\Hrm\LoanInstallment::find($statutory['loan_installment_id'])
                : null;

            if ($loan) {
                $this->loans->recordRecovery($loan, $installment, $statutory['loan_deduction'], $period);
            }
        }
    }
}
