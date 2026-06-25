<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInstallment extends Model
{
    protected $table = 'hrm_loan_installments';

    protected $fillable = [
        'loan_account_id', 'payroll_period_id', 'installment_no',
        'due_date', 'amount', 'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount'   => 'decimal:2',
    ];

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
