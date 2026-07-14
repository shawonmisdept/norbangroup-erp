<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class EmployeeSeparation extends Model
{
    public const STATUSES = [
        'pending'   => 'Pending',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    protected $table = 'hrm_employee_separations';

    protected $fillable = [
        'factory_id', 'employee_id', 'separation_type', 'source', 'status',
        'application_date', 'last_working_day', 'notice_period_days',
        'reason', 'remarks', 'attachment_path', 'current_approval_step',
        'applied_at', 'approved_at', 'rejected_at',
        'initiated_by_user_id', 'approved_by', 'rejected_by', 'rejection_reason',
        'exit_clearance', 'exit_interview_notes', 'exit_interview_at',
    ];

    protected $casts = [
        'application_date'      => 'date',
        'last_working_day'      => 'date',
        'applied_at'            => 'datetime',
        'approved_at'           => 'datetime',
        'rejected_at'           => 'datetime',
        'exit_clearance'        => 'array',
        'exit_interview_at'     => 'datetime',
        'current_approval_step' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (EmployeeSeparation $separation) {
            if ($separation->attachment_path) {
                Storage::disk('public')->delete($separation->attachment_path);
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

    public function initiatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
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
        return $this->hasMany(EmployeeSeparationApproval::class)->orderBy('step');
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function typeLabel(): string
    {
        return config("hrm.separation_types.{$this->separation_type}.label", ucfirst($this->separation_type));
    }

    public static function defaultExitClearance(): array
    {
        return array_fill_keys(array_keys(config('hrm.exit_clearance_departments', [])), false);
    }

    public function exitClearanceComplete(): bool
    {
        $clearance = $this->exit_clearance ?? self::defaultExitClearance();

        return collect($clearance)->every(fn ($v) => (bool) $v);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
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
