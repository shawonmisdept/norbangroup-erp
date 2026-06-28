<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceBonusBand extends Model
{
    protected $table = 'hrm_performance_bonus_bands';

    protected $fillable = [
        'factory_id', 'name', 'min_score', 'max_score', 'bonus_percent', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'min_score'     => 'decimal:2',
        'max_score'     => 'decimal:2',
        'bonus_percent' => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function matchesScore(float $score): bool
    {
        return $score >= (float) $this->min_score && $score <= (float) $this->max_score;
    }
}
