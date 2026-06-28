<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceIncrementItem extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'applied' => 'Applied',
        'skipped' => 'Skipped',
        'failed'  => 'Failed',
    ];

    protected $table = 'hrm_performance_increment_items';

    protected $fillable = [
        'performance_increment_run_id', 'employee_id', 'performance_review_id',
        'overall_score', 'band_name', 'increment_percent', 'override_increment_percent',
        'previous_gross', 'suggested_new_gross', 'override_new_gross', 'final_new_gross',
        'increment_amount', 'status', 'salary_increment_log_id', 'error_message', 'notes',
    ];

    protected $casts = [
        'overall_score'              => 'decimal:2',
        'increment_percent'          => 'decimal:2',
        'override_increment_percent' => 'decimal:2',
        'previous_gross'             => 'decimal:2',
        'suggested_new_gross'        => 'decimal:2',
        'override_new_gross'         => 'decimal:2',
        'final_new_gross'            => 'decimal:2',
        'increment_amount'           => 'decimal:2',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PerformanceIncrementRun::class, 'performance_increment_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class, 'performance_review_id');
    }

    public function salaryIncrementLog(): BelongsTo
    {
        return $this->belongsTo(SalaryIncrementLog::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function resolvedIncrementPercent(): float
    {
        return (float) ($this->override_increment_percent ?? $this->increment_percent);
    }

    public function resolvedNewGross(): float
    {
        if ($this->override_new_gross !== null) {
            return (float) $this->override_new_gross;
        }

        if ($this->override_increment_percent !== null) {
            return round((float) $this->previous_gross * (1 + ((float) $this->override_increment_percent / 100)), 2);
        }

        return (float) $this->final_new_gross;
    }
}
