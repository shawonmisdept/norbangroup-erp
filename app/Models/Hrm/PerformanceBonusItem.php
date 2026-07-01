<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceBonusItem extends Model
{
    protected $table = 'hrm_performance_bonus_items';

    protected $fillable = [
        'performance_bonus_run_id', 'employee_id', 'performance_review_id',
        'overall_score', 'band_name', 'bonus_percent', 'base_amount',
        'bonus_amount', 'override_amount', 'final_amount', 'notes', 'payroll_period_id',
    ];

    protected $casts = [
        'overall_score'   => 'decimal:2',
        'bonus_percent'   => 'decimal:2',
        'base_amount'     => 'decimal:2',
        'bonus_amount'    => 'decimal:2',
        'override_amount' => 'decimal:2',
        'final_amount'    => 'decimal:2',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PerformanceBonusRun::class, 'performance_bonus_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class, 'performance_review_id');
    }

    public function resolvedAmount(): float
    {
        return (float) ($this->override_amount ?? $this->bonus_amount);
    }
}
