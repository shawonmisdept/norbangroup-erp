<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceReview extends Model
{
    public const STATUSES = [
        'draft'          => 'Draft',
        'blocked'        => 'Blocked',
        'pending_rating' => 'Pending Rating',
        'pending_hr'     => 'Pending HR Approval',
        'approved'       => 'Approved',
        'rejected'       => 'Rejected',
        'cancelled'      => 'Cancelled',
    ];

    protected $table = 'hrm_performance_reviews';

    protected $fillable = [
        'factory_id', 'cycle_id', 'employee_id', 'template_id', 'cycle_type', 'status',
        'period_from', 'period_to', 'reporting_to_id', 'overall_score', 'auto_metrics',
        'manual_fallback', 'probation_recommendation', 'apply_confirmation', 'blocked_reason',
        'rated_by_user_id', 'rated_on_behalf_of_id', 'rated_at',
        'hr_approved_by', 'hr_approved_at', 'hr_rejected_by', 'hr_rejected_at', 'hr_rejection_reason',
        'rating_notes', 'created_by',
    ];

    protected $casts = [
        'period_from'        => 'date',
        'period_to'          => 'date',
        'overall_score'      => 'decimal:2',
        'auto_metrics'       => 'array',
        'manual_fallback'    => 'boolean',
        'apply_confirmation' => 'boolean',
        'rated_at'           => 'datetime',
        'hr_approved_at'     => 'datetime',
        'hr_rejected_at'     => 'datetime',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceTemplate::class, 'template_id');
    }

    public function reportingTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_to_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PerformanceScore::class, 'review_id');
    }

    public function ratedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_by_user_id');
    }

    public function ratedOnBehalfOf(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'rated_on_behalf_of_id');
    }

    public function hrApprovedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_approved_by');
    }

    public function hrRejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_rejected_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cycleTypeLabel(): string
    {
        return PerformanceCycle::CYCLE_TYPES[$this->cycle_type] ?? ucfirst(str_replace('_', ' ', $this->cycle_type));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isPendingRating(): bool
    {
        return $this->status === 'pending_rating';
    }

    public function isPendingHr(): bool
    {
        return $this->status === 'pending_hr';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function passedMinimumScore(): bool
    {
        return $this->overall_score !== null && (float) $this->overall_score >= config('hrm.performance.minimum_pass_score', 60);
    }
}
