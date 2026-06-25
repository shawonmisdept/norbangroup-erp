<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanAccount extends Model
{
    protected $table = 'hrm_loan_accounts';

    protected $fillable = [
        'factory_id', 'employee_id', 'loan_type', 'principal', 'balance',
        'emi_amount', 'total_installments', 'paid_installments', 'status',
        'notes', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'principal'  => 'decimal:2',
        'balance'    => 'decimal:2',
        'emi_amount' => 'decimal:2',
        'approved_at'=> 'datetime',
    ];

    public const LOAN_TYPES = [
        'advance'   => 'Salary Advance',
        'emergency' => 'Emergency Loan',
    ];

    public const STATUSES = [
        'pending'  => 'Pending Approval',
        'active'   => 'Active',
        'closed'   => 'Closed',
        'rejected' => 'Rejected',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class)->orderBy('installment_no');
    }

    public function loanTypeLabel(): string
    {
        return self::LOAN_TYPES[$this->loan_type] ?? ucfirst($this->loan_type);
    }

    public static function calculateEmi(float $principal, int $totalInstallments): float
    {
        if ($totalInstallments < 1) {
            return round($principal, 2);
        }

        return round($principal / $totalInstallments, 2);
    }
}
