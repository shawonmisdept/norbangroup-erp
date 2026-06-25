<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSeparationApproval extends Model
{
    public const STATUSES = [
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'skipped'  => 'Skipped',
    ];

    protected $table = 'hrm_employee_separation_approvals';

    protected $fillable = [
        'employee_separation_id', 'step', 'step_label', 'status',
        'approver_employee_id', 'acted_by', 'acted_by_employee_id', 'acted_at', 'notes',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function separation(): BelongsTo
    {
        return $this->belongsTo(EmployeeSeparation::class, 'employee_separation_id');
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
