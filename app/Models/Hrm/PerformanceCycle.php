<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceCycle extends Model
{
    public const STATUSES = [
        'open'   => 'Open',
        'closed' => 'Closed',
    ];

    public const CYCLE_TYPES = [
        'probation_6m' => 'Probation (6 months)',
        'mid_year_6m'  => 'Mid-Year Bonus (January)',
        'annual_12m'   => 'Annual Increment (12 months)',
    ];

    protected $table = 'hrm_performance_cycles';

    protected $fillable = [
        'factory_id', 'cycle_type', 'name', 'year', 'period_from', 'period_to',
        'status', 'template_id', 'opened_by', 'opened_at', 'closed_at', 'notes', 'review_count',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to'   => 'date',
        'opened_at'   => 'datetime',
        'closed_at'   => 'datetime',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceTemplate::class, 'template_id');
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class, 'cycle_id');
    }

    public function bonusRuns(): HasMany
    {
        return $this->hasMany(PerformanceBonusRun::class, 'performance_cycle_id');
    }

    public function incrementRuns(): HasMany
    {
        return $this->hasMany(PerformanceIncrementRun::class, 'performance_cycle_id');
    }

    public function cycleTypeLabel(): string
    {
        return self::CYCLE_TYPES[$this->cycle_type] ?? ucfirst(str_replace('_', ' ', $this->cycle_type));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
