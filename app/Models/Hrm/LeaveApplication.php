<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class LeaveApplication extends Model
{
    public const STATUSES = [
        'pending'   => 'Pending',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    protected $table = 'hrm_leave_applications';

    protected $fillable = [
        'factory_id', 'employee_id', 'leave_type_id',
        'start_date', 'end_date', 'total_days', 'reason', 'attachment_path',
        'status', 'applied_at', 'approved_at', 'rejected_at',
        'approved_by', 'rejected_by', 'rejection_reason', 'current_approval_step',
    ];

    protected $casts = [
        'start_date'            => 'date',
        'end_date'              => 'date',
        'total_days'            => 'decimal:1',
        'applied_at'            => 'datetime',
        'approved_at'           => 'datetime',
        'rejected_at'           => 'datetime',
        'current_approval_step' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (LeaveApplication $application) {
            if ($application->attachment_path) {
                Storage::disk('public')->delete($application->attachment_path);
            }
        });
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class)->orderBy('step');
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function dateRangeLabel(): string
    {
        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->format('d M Y');
        }

        return $this->start_date->format('d M') . ' – ' . $this->end_date->format('d M Y');
    }

    public function pendingStepLabel(): ?string
    {
        if (! $this->isPending()) {
            return null;
        }

        return match ((int) $this->current_approval_step) {
            1       => 'Awaiting Reporting Person',
            2       => 'Awaiting HR',
            default => 'Pending',
        };
    }

    public function attachmentUrl(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }
}
