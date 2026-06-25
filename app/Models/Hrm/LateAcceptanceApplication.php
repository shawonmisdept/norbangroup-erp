<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LateAcceptanceApplication extends Model
{
    public const STATUSES = [
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    protected $table = 'hrm_late_acceptance_applications';

    protected $fillable = [
        'factory_id', 'employee_id', 'attendance_date', 'reason', 'status',
        'applied_at', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'applied_at'      => 'datetime',
        'approved_at'     => 'datetime',
        'rejected_at'     => 'datetime',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function dailyLog(): HasOne
    {
        return $this->hasOne(AttendanceDailyLog::class, 'late_acceptance_application_id');
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
