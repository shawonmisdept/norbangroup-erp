<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeeTaxLedger;
use App\Models\Hrm\TaxYear;
use Illuminate\Support\Collection;

class TaxCertificateService
{
    /** @return array{employee: Employee, taxYear: TaxYear, ledgers: Collection, totalTaxable: float, totalTds: float} */
    public function build(Employee $employee, TaxYear $taxYear): array
    {
        $ledgers = EmployeeTaxLedger::query()
            ->where('employee_id', $employee->id)
            ->where('tax_year_id', $taxYear->id)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return [
            'employee'     => $employee->loadMissing(['factory', 'department', 'designation']),
            'taxYear'      => $taxYear->loadMissing('factory'),
            'ledgers'      => $ledgers,
            'totalTaxable' => round((float) $ledgers->sum('taxable_income'), 2),
            'totalTds'     => round((float) $ledgers->sum('tds_amount'), 2),
        ];
    }
}
