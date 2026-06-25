<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTaxLedger extends Model
{
    protected $table = 'hrm_employee_tax_ledgers';

    protected $fillable = [
        'factory_id', 'employee_id', 'tax_year_id', 'payroll_period_id',
        'year', 'month', 'taxable_income', 'tds_amount',
    ];

    protected $casts = [
        'taxable_income' => 'decimal:2',
        'tds_amount'     => 'decimal:2',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function taxYear(): BelongsTo
    {
        return $this->belongsTo(TaxYear::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
