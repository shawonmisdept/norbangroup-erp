<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PfContribution extends Model
{
    protected $table = 'hrm_pf_contributions';

    protected $fillable = [
        'pf_account_id', 'payroll_period_id', 'year', 'month',
        'base_amount', 'employee_amount', 'employer_amount',
    ];

    protected $casts = [
        'base_amount'     => 'decimal:2',
        'employee_amount' => 'decimal:2',
        'employer_amount' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(PfAccount::class, 'pf_account_id');
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
