<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryBank extends Model
{
    protected $table = 'hrm_salary_banks';

    protected $fillable = [
        'factory_id', 'code', 'name', 'short_name', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class, 'salary_bank_id');
    }

    public function displayName(): string
    {
        return $this->short_name ?: $this->name;
    }
}
