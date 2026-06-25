<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProxyPunchFlag extends Model
{
    public const STATUSES = [
        'open'      => 'Open',
        'reviewed'  => 'Reviewed',
        'dismissed' => 'Dismissed',
        'confirmed' => 'Confirmed',
    ];

    protected $table = 'hrm_proxy_punch_flags';

    protected $fillable = [
        'factory_id', 'attendance_raw_punch_id', 'employee_id', 'reason', 'status',
        'flagged_by', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function punch(): BelongsTo
    {
        return $this->belongsTo(AttendanceRawPunch::class, 'attendance_raw_punch_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
