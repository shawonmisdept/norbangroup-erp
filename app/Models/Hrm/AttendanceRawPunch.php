<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRawPunch extends Model
{
    public const SOURCES = [
        'iclock_push' => 'ZKTeco SpeedFace',
        'adms_pull'   => 'ADMS Pull',
        'adms_push'   => 'ADMS Push',
        'mobile_gps'  => 'Mobile Check-in',
        'qr_scan'     => 'QR Gate Scan',
        'manual_hr'   => 'HR Manual',
        'manual'      => 'Manual Import',
    ];

    public const PUNCH_TYPES = [
        'in'      => 'Check In',
        'out'     => 'Check Out',
        'unknown' => 'Unknown',
    ];

    protected $table = 'hrm_attendance_raw_punches';

    protected $fillable = [
        'factory_id', 'biometric_device_id', 'employee_id',
        'device_serial', 'biometric_user_id', 'punched_at',
        'punch_type', 'source', 'external_id', 'raw_payload', 'processed_at',
        'latitude', 'longitude', 'geo_distance_m', 'photo_path',
        'gate_point_id', 'entered_by_user_id', 'reason',
    ];

    protected $casts = [
        'punched_at'    => 'datetime',
        'processed_at'  => 'datetime',
        'raw_payload'   => 'array',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function biometricDevice(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function gatePoint(): BelongsTo
    {
        return $this->belongsTo(AttendanceGatePoint::class, 'gate_point_id');
    }

    public function enteredByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'entered_by_user_id');
    }

    public function punchTypeLabel(): string
    {
        return static::PUNCH_TYPES[$this->punch_type] ?? ucfirst($this->punch_type);
    }

    public function sourceLabel(): string
    {
        return static::SOURCES[$this->source] ?? ucfirst($this->source);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }
}
