<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TmsDriver extends Model
{
    use SoftDeletes;

    protected $table = 'tms_drivers';

    protected $fillable = [
        'factory_id', 'employee_id', 'default_vehicle_id', 'license_number', 'ot_rate', 'is_overtime_active',
        'ot_rate_effective_from', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'ot_rate'               => 'decimal:2',
            'is_overtime_active'    => 'boolean',
            'ot_rate_effective_from' => 'date',
        ];
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function defaultVehicle(): BelongsTo
    {
        return $this->belongsTo(TmsVehicle::class, 'default_vehicle_id');
    }

    public function tripLogs(): HasMany
    {
        return $this->hasMany(TmsTripLog::class, 'driver_id');
    }

    public function otRateLogs(): HasMany
    {
        return $this->hasMany(TmsDriverOtRateLog::class, 'driver_id')->latest('id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function displayLabel(): string
    {
        return $this->employee?->name ?? "Driver #{$this->id}";
    }

    public function contactPhone(): ?string
    {
        $phone = trim((string) ($this->employee?->phone ?? ''));

        return $phone !== '' ? $phone : null;
    }
}
