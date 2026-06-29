<?php

namespace App\Models\Tms;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TmsTripLog extends Model
{
    protected $table = 'tms_trip_logs';

    protected $fillable = [
        'transport_request_id', 'factory_id', 'vehicle_id', 'driver_id', 'rental_driver_id', 'driver_type', 'total_passengers',
        'start_km', 'end_km', 'total_km', 'duty_start_at', 'duty_end_at',
        'ot_hours', 'ot_amount', 'ot_hourly_amount', 'ot_start_at', 'ot_end_at',
        'bill_type', 'night_bill_amount', 'holiday_duty_amount', 'total_driver_pay',
        'rental_km_rate', 'rental_charge_amount', 'trip_status',
    ];

    protected function casts(): array
    {
        return [
            'total_passengers'    => 'integer',
            'start_km'            => 'decimal:2',
            'end_km'              => 'decimal:2',
            'total_km'            => 'decimal:2',
            'ot_hours'            => 'decimal:2',
            'ot_amount'           => 'decimal:2',
            'ot_hourly_amount'    => 'decimal:2',
            'night_bill_amount'   => 'decimal:2',
            'holiday_duty_amount' => 'decimal:2',
            'total_driver_pay'    => 'decimal:2',
            'rental_km_rate'      => 'decimal:2',
            'rental_charge_amount'=> 'decimal:2',
            'duty_start_at'       => 'datetime',
            'duty_end_at'         => 'datetime',
            'ot_start_at'         => 'datetime',
            'ot_end_at'           => 'datetime',
        ];
    }

    public function transportRequest(): BelongsTo
    {
        return $this->belongsTo(TmsTransportRequest::class, 'transport_request_id');
    }

    public function transportRequests(): HasMany
    {
        return $this->hasMany(TmsTransportRequest::class, 'trip_log_id');
    }

    public function primaryRequest(): ?TmsTransportRequest
    {
        $linked = $this->relationLoaded('transportRequests')
            ? $this->transportRequests
            : $this->transportRequests()->get();

        if ($linked->isNotEmpty()) {
            return $linked->first();
        }

        return $this->transportRequest;
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TmsVehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TmsDriver::class, 'driver_id');
    }

    public function rentalDriver(): BelongsTo
    {
        return $this->belongsTo(TmsRentalDriver::class, 'rental_driver_id');
    }

    public function assignedDriverLabel(): string
    {
        if ($this->rentalDriver) {
            return $this->rentalDriver->displayLabel() . ' (Rental)';
        }

        return $this->driver?->displayLabel() ?? '—';
    }

    public function overtimePayment(): HasOne
    {
        return $this->hasOne(TmsDriverOvertimePayment::class, 'trip_log_id');
    }

    public function rentalVehicleCharge(): HasOne
    {
        return $this->hasOne(TmsRentalVehicleCharge::class, 'trip_log_id');
    }

    public function fuelLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TmsFuelLog::class, 'trip_log_id');
    }

    public function tripStatusLabel(): string
    {
        return config("tms.trip_statuses.{$this->trip_status}", ucfirst((string) $this->trip_status));
    }

    public function tripStatusBadgeClass(): string
    {
        return config("tms.trip_status_colors.{$this->trip_status}", 'bg-gray-100 text-gray-600');
    }
}
