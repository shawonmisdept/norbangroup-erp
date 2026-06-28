<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceBonusRun extends Model
{
    public const STATUSES = [
        'draft'      => 'Draft',
        'calculated' => 'Calculated',
        'approved'   => 'Approved',
    ];

    public const BONUS_BASES = [
        'gross' => 'Monthly Gross',
        'basic' => 'Basic Salary',
    ];

    protected $table = 'hrm_performance_bonus_runs';

    protected $fillable = [
        'factory_id', 'performance_cycle_id', 'year', 'name', 'bonus_base', 'status', 'notes',
        'calculated_by', 'calculated_at', 'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'approved_at'   => 'datetime',
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
        return $this->hasMany(PerformanceBonusItem::class, 'performance_bonus_run_id');
    }

    public function calculatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function bonusBaseLabel(): string
    {
        return self::BONUS_BASES[$this->bonus_base] ?? ucfirst($this->bonus_base);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function totalBonus(): float
    {
        return (float) $this->items()->sum('final_amount');
    }
}
