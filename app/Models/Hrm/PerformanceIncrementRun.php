<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceIncrementRun extends Model
{
    public const STATUSES = [
        'draft'      => 'Draft',
        'calculated' => 'Calculated',
        'applied'    => 'Applied',
    ];

    protected $table = 'hrm_performance_increment_runs';

    protected $fillable = [
        'factory_id', 'performance_cycle_id', 'year', 'name', 'status', 'notes',
        'calculated_by', 'calculated_at', 'applied_by', 'applied_at', 'created_by',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'applied_at'    => 'datetime',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PerformanceIncrementItem::class, 'performance_increment_run_id');
    }

    public function calculatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }
}
