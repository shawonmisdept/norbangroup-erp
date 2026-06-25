<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApproval extends Model
{
    public const STATUSES = [
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'skipped'  => 'Skipped',
    ];

    public const STEPS = [
        1 => 'Reporting Person',
        2 => 'HR',
    ];

    protected $table = 'hrm_leave_approvals';

    protected $fillable = [
        'leave_application_id', 'step', 'step_label', 'status',
        'approver_employee_id', 'acted_by', 'acted_by_employee_id', 'acted_at', 'notes',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class, 'leave_application_id');
    }

    public function actedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    public function actedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'acted_by_employee_id');
    }

    public function approverEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function actorName(): ?string
    {
        return $this->actedByEmployee?->name
            ?? $this->actedByUser?->name;
    }
}
