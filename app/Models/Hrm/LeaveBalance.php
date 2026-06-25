<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $table = 'hrm_leave_balances';

    protected $fillable = [
        'factory_id', 'employee_id', 'leave_type_id', 'year',
        'entitled_days', 'used_days', 'pending_days',
    ];

    protected $casts = [
        'year'          => 'integer',
        'entitled_days' => 'decimal:1',
        'used_days'     => 'decimal:1',
        'pending_days'  => 'decimal:1',
    ];

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

    public function availableDays(): float
    {
        return max(0, (float) $this->entitled_days - (float) $this->used_days - (float) $this->pending_days);
    }
}
