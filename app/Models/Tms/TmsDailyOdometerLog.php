<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use App\Support\PortalDateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TmsDailyOdometerLog extends Model
{
    protected $table = 'tms_daily_odometer_logs';

    protected $fillable = [
        'factory_id', 'vehicle_id', 'log_date', 'morning_km', 'morning_recorded_at',
        'evening_km', 'evening_recorded_at', 'notes',
        'morning_entered_by', 'evening_entered_by',
        'morning_entered_by_employee', 'evening_entered_by_employee',
        'morning_entered_by_rental_driver', 'evening_entered_by_rental_driver',
    ];

    protected function casts(): array
    {
        return [
            'log_date'            => 'date',
            'morning_km'          => 'decimal:2',
            'evening_km'          => 'decimal:2',
            'morning_recorded_at' => 'datetime',
            'evening_recorded_at' => 'datetime',
        ];
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TmsVehicle::class, 'vehicle_id');
    }

    public function rentalCharge(): HasOne
    {
        return $this->hasOne(TmsRentalVehicleCharge::class, 'odometer_log_id');
    }

    public function dailyKm(): ?float
    {
        if ($this->morning_km !== null && $this->evening_km !== null) {
            return round((float) $this->evening_km - (float) $this->morning_km, 2);
        }

        return null;
    }

    public function hasMorning(): bool
    {
        return $this->morning_km !== null;
    }

    public function hasEvening(): bool
    {
        return $this->evening_km !== null;
    }

    public function needsEvening(): bool
    {
        return $this->hasMorning() && ! $this->hasEvening();
    }

    public function statusLabel(): string
    {
        if ($this->hasMorning() && $this->hasEvening()) {
            return 'Complete';
        }

        if ($this->hasMorning()) {
            return 'Evening Pending';
        }

        return 'Incomplete';
    }

    public function statusBadgeClass(): string
    {
        if ($this->hasMorning() && $this->hasEvening()) {
            return 'bg-green-100 text-green-800';
        }

        if ($this->hasMorning()) {
            return 'bg-amber-100 text-amber-800';
        }

        return 'bg-gray-100 text-gray-600';
    }

    public function morningEnteredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'morning_entered_by');
    }

    public function eveningEnteredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evening_entered_by');
    }

    public function morningEnteredByEmployee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Hrm\Employee::class, 'morning_entered_by_employee');
    }

    public function eveningEnteredByEmployee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Hrm\Employee::class, 'evening_entered_by_employee');
    }

    public function morningRecordedTime(): ?string
    {
        return $this->morning_recorded_at
            ? PortalDateTime::time($this->morning_recorded_at)
            : null;
    }

    public function eveningRecordedTime(): ?string
    {
        return $this->evening_recorded_at
            ? PortalDateTime::time($this->evening_recorded_at)
            : null;
    }
}
