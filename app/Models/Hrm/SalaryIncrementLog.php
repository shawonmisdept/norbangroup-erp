<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryIncrementLog extends Model
{
    protected $table = 'hrm_salary_increment_logs';

    protected $fillable = [
        'factory_id',
        'salary_increment_rule_id',
        'employee_id',
        'performance_review_id',
        'performance_increment_run_id',
        'previous_gross',
        'new_gross',
        'applied_by',
        'applied_at',
    ];

    protected $casts = [
        'previous_gross' => 'decimal:2',
        'new_gross'      => 'decimal:2',
        'applied_at'     => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(SalaryIncrementRule::class, 'salary_increment_rule_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function performanceReview(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class, 'performance_review_id');
    }

    public function performanceIncrementRun(): BelongsTo
    {
        return $this->belongsTo(PerformanceIncrementRun::class, 'performance_increment_run_id');
    }

    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}
