<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AttendanceGatePoint extends Model
{
    protected $table = 'hrm_attendance_gate_points';

    protected $fillable = [
        'factory_id', 'code', 'name', 'location',
        'latitude', 'longitude', 'qr_token', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $point) {
            if (blank($point->qr_token)) {
                $point->qr_token = Str::random(32);
            }
        });
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function checkInUrl(): string
    {
        return route('employee.attendance.check-in', ['gate' => $this->qr_token]);
    }
}
