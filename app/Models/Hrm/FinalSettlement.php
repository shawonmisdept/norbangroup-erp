<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalSettlement extends Model
{
    protected $table = 'hrm_final_settlements';

    protected $fillable = [
        'factory_id', 'employee_id', 'separation_type', 'last_working_day', 'status',
        'unpaid_salary', 'leave_encashment', 'gratuity_amount', 'pf_withdrawal',
        'loan_deduction', 'tax_deduction', 'other_earnings', 'other_deductions', 'net_payable',
        'breakdown', 'clearance', 'gratuity_settlement_id', 'notes',
        'calculated_by', 'approved_by', 'calculated_at', 'approved_at', 'paid_at',
    ];

    protected $casts = [
        'last_working_day'  => 'date',
        'unpaid_salary'     => 'decimal:2',
        'leave_encashment'  => 'decimal:2',
        'gratuity_amount'   => 'decimal:2',
        'pf_withdrawal'     => 'decimal:2',
        'loan_deduction'    => 'decimal:2',
        'tax_deduction'     => 'decimal:2',
        'other_earnings'    => 'decimal:2',
        'other_deductions'  => 'decimal:2',
        'net_payable'       => 'decimal:2',
        'breakdown'         => 'array',
        'clearance'         => 'array',
        'calculated_at'     => 'datetime',
        'approved_at'       => 'datetime',
        'paid_at'           => 'datetime',
    ];

    public const STATUSES = [
        'draft'      => 'Draft',
        'calculated' => 'Calculated',
        'approved'   => 'Approved',
        'paid'       => 'Paid',
    ];

    public const CLEARANCE_KEYS = [
        'hr'         => 'HR',
        'it'         => 'IT',
        'stores'     => 'Stores',
        'accounts'   => 'Accounts',
        'line_chief' => 'Line Chief',
    ];

    public static function defaultClearance(): array
    {
        return array_fill_keys(array_keys(self::CLEARANCE_KEYS), false);
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function gratuitySettlement(): BelongsTo
    {
        return $this->belongsTo(GratuitySettlement::class);
    }

    public function calculator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function totalEarnings(): float
    {
        return round(
            (float) $this->unpaid_salary
            + (float) $this->leave_encashment
            + (float) $this->gratuity_amount
            + (float) $this->pf_withdrawal
            + (float) $this->other_earnings,
            2
        );
    }

    public function totalDeductions(): float
    {
        return round(
            (float) $this->loan_deduction
            + (float) $this->tax_deduction
            + (float) $this->other_deductions,
            2
        );
    }

    public function clearanceComplete(): bool
    {
        $clearance = array_merge(self::defaultClearance(), $this->clearance ?? []);

        return collect($clearance)->every(fn ($done) => (bool) $done);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
