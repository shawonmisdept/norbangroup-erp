<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    public const PAY_TYPES = [
        'salary' => 'Monthly Salary',
        'wages'  => 'Daily Wages',
    ];

    public const PAYMENT_METHODS = [
        'bank' => 'Bank',
        'cash' => 'Cash',
    ];

    protected $table = 'hrm_salary_structures';

    protected $fillable = [
        'factory_id', 'employee_id', 'salary_grade_id', 'gross_salary', 'head_amounts',
        'pay_type', 'basic_salary', 'daily_wage', 'hra', 'medical', 'conveyance', 'other_allowance',
        'payment_method', 'bank_account', 'effective_from', 'is_active',
    ];

    protected $casts = [
        'gross_salary'     => 'decimal:2',
        'head_amounts'     => 'array',
        'basic_salary'     => 'decimal:2',
        'daily_wage'       => 'decimal:2',
        'hra'              => 'decimal:2',
        'medical'          => 'decimal:2',
        'conveyance'       => 'decimal:2',
        'other_allowance'  => 'decimal:2',
        'effective_from'   => 'date',
        'is_active'        => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class);
    }

    public function resolveFactoryId(): int
    {
        if ($this->factory_id) {
            return (int) $this->factory_id;
        }

        $this->loadMissing('employee');
        $factoryId = (int) ($this->employee?->factory_id ?? 0);

        if ($factoryId) {
            $this->forceFill(['factory_id' => $factoryId])->saveQuietly();
        }

        return $factoryId;
    }

    public function payTypeLabel(): string
    {
        return static::PAY_TYPES[$this->pay_type] ?? ucfirst($this->pay_type);
    }

    /** @param array<string, float> $amounts head code => amount */
    public function syncLegacyFromHeads(array $amounts): void
    {
        $this->head_amounts = $amounts;
        $this->gross_salary = $amounts['GROSS'] ?? $this->gross_salary ?? 0;
        $this->basic_salary = $amounts['BASIC'] ?? $this->gross_salary ?? 0;
        $this->hra = $amounts['HRA'] ?? $amounts['HOUSE RENT'] ?? 0;
        $this->medical = $amounts['MEDICAL'] ?? $amounts['MED'] ?? 0;
        $this->conveyance = $amounts['CONVEYANCE'] ?? $amounts['CONV'] ?? 0;
        $this->other_allowance = ($amounts['FOOD ALLOWANCE'] ?? 0)
            + ($amounts['PERFORMANCE BONUS'] ?? 0)
            + ($amounts['OTHER'] ?? 0);
        $this->pay_type = 'salary';
    }

    public function headAmount(string $code): float
    {
        $key = strtoupper(trim($code));

        return (float) ($this->head_amounts[$key] ?? 0);
    }

    public function totalAllowances(): float
    {
        if ($this->head_amounts) {
            return round(
                $this->headAmount('HRA')
                + $this->headAmount('HOUSE RENT')
                + $this->headAmount('MEDICAL')
                + $this->headAmount('MED')
                + $this->headAmount('CONVEYANCE')
                + $this->headAmount('CONV')
                + $this->headAmount('FOOD ALLOWANCE')
                + $this->headAmount('PERFORMANCE BONUS')
                + (float) $this->other_allowance,
                2
            );
        }

        return (float) $this->hra + (float) $this->medical + (float) $this->conveyance + (float) $this->other_allowance;
    }

    public function monthlyGross(): float
    {
        if ((float) $this->gross_salary > 0) {
            return (float) $this->gross_salary;
        }

        if ($this->pay_type === 'wages') {
            return (float) $this->daily_wage * 26 + $this->totalAllowances();
        }

        return (float) $this->basic_salary + $this->totalAllowances();
    }
}
