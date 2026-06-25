<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryIncrementRule extends Model
{
    public const TYPES = [
        'percentage' => 'Percentage of Gross',
        'fixed'      => 'Fixed Amount (৳)',
    ];

    protected $table = 'hrm_salary_increment_rules';

    protected $fillable = [
        'factory_id',
        'salary_grade_id',
        'name',
        'increment_type',
        'increment_value',
        'min_tenure_months',
        'description',
        'is_active',
    ];

    protected $casts = [
        'increment_value'    => 'decimal:2',
        'min_tenure_months'  => 'integer',
        'is_active'          => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function salaryGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SalaryIncrementLog::class);
    }

    public function typeLabel(): string
    {
        return static::TYPES[$this->increment_type] ?? ucfirst($this->increment_type);
    }

    public function applyToGross(float $currentGross): float
    {
        if ($this->increment_type === 'fixed') {
            return round($currentGross + (float) $this->increment_value, 2);
        }

        return round($currentGross * (1 + ((float) $this->increment_value / 100)), 2);
    }

    public function valueLabel(): string
    {
        if ($this->increment_type === 'fixed') {
            return '৳' . number_format((float) $this->increment_value, 2);
        }

        return number_format((float) $this->increment_value, 2) . '%';
    }
}
