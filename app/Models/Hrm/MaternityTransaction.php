<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaternityTransaction extends Model
{
    protected $table = 'hrm_maternity_transactions';

    protected $fillable = [
        'factory_id', 'employee_id', 'leave_application_id',
        'expected_delivery_date', 'start_date', 'end_date',
        'paid_weeks', 'unpaid_weeks', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'start_date'             => 'date',
        'end_date'               => 'date',
    ];

    public const STATUSES = [
        'pending'   => 'Pending',
        'active'    => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
