<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryGradeDetail extends Model
{
    public const DETAIL_TYPES = [
        'M' => 'Manual Formula',
        'F' => 'Fixed',
        'P' => 'Percentage',
    ];

    protected $table = 'hrm_salary_grade_details';

    protected $fillable = [
        'salary_grade_id', 'salary_head_id', 'detail_type', 'is_fixed',
        'amount', 'percentage', 'percentage_of_head_id', 'formula',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'percentage' => 'decimal:2',
        'is_fixed'   => 'boolean',
    ];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class, 'salary_grade_id');
    }

    public function salaryHead(): BelongsTo
    {
        return $this->belongsTo(SalaryHead::class);
    }

    public function percentageOfHead(): BelongsTo
    {
        return $this->belongsTo(SalaryHead::class, 'percentage_of_head_id');
    }

    public function detailTypeLabel(): string
    {
        return static::DETAIL_TYPES[$this->detail_type] ?? $this->detail_type;
    }

    public function displayEntry(): string
    {
        return match ($this->detail_type) {
            'M'     => 'Formula',
            'P'     => number_format((float) $this->percentage, 2) . '%'
                . ($this->percentageOfHead ? ' of ' . $this->percentageOfHead->name : ''),
            default => 'Fixed',
        };
    }
}
