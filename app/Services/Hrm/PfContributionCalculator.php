<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\PfAccount;

class PfContributionCalculator
{
    public function calculate(Employee $employee, float $basicAmount): array
    {
        $account = PfAccount::query()
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->first();

        if (! $account || $basicAmount <= 0) {
            return [
                'pf_account_id'   => null,
                'base_amount'     => 0.0,
                'employee_amount' => 0.0,
                'employer_amount' => 0.0,
            ];
        }

        $employeeAmount = round($basicAmount * ((float) $account->employee_rate_pct / 100), 2);
        $employerAmount = round($basicAmount * ((float) $account->employer_rate_pct / 100), 2);

        return [
            'pf_account_id'   => $account->id,
            'base_amount'     => round($basicAmount, 2),
            'employee_amount' => $employeeAmount,
            'employer_amount' => $employerAmount,
        ];
    }
}
