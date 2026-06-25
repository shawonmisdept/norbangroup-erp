<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePolicy extends Model
{
    protected $table = 'hrm_leave_policies';

    protected $fillable = [
        'factory_id', 'leave_type_id', 'days_per_year',
        'min_days_notice', 'requires_medical_after_days',
        'requires_attachment', 'is_active',
    ];

    protected $casts = [
        'days_per_year'               => 'decimal:1',
        'requires_attachment'         => 'boolean',
        'is_active'                   => 'boolean',
        'requires_medical_after_days' => 'integer',
        'min_days_notice'             => 'integer',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
