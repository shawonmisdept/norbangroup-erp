<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryHead extends Model
{
    public const HEAD_TYPES = [
        'E' => 'Earning',
        'D' => 'Deduction',
        'S' => 'Statutory',
    ];

    /** @deprecated Use detail_type on grade details instead */
    public const CALCULATION_TYPES = [
        'fixed'      => 'Fixed Amount',
        'percentage' => 'Percentage',
    ];

    protected $table = 'hrm_salary_heads';

    protected $fillable = [
        'factory_id', 'code', 'name', 'name_bangla', 'description',
        'head_type', 'calculation_type', 'is_taxable', 'sort_order', 'sort_code',
        'is_perquisite', 'is_disburse', 'is_active',
    ];

    protected $casts = [
        'is_taxable'    => 'boolean',
        'is_perquisite' => 'boolean',
        'is_disburse'   => 'boolean',
        'is_active'     => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function gradeDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalaryGradeDetail::class);
    }

    public function headTypeLabel(): string
    {
        return static::HEAD_TYPES[$this->head_type] ?? $this->head_type;
    }
}
