<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryGrade extends Model
{
    protected $table = 'hrm_salary_grades';

    protected $fillable = [
        'factory_id', 'code', 'name', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SalaryGradeDetail::class)->orderBy('id');
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class);
    }

    public function totalEarnings(): float
    {
        return (float) $this->details()
            ->whereHas('salaryHead', fn ($q) => $q->where('head_type', 'earning'))
            ->sum('amount');
    }
}
