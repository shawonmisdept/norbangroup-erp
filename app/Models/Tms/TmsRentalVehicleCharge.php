<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsRentalVehicleCharge extends Model
{
    protected $table = 'tms_rental_vehicle_charges';

    protected $fillable = [
        'trip_log_id', 'odometer_log_id', 'log_date', 'factory_id', 'vehicle_id', 'rental_vendor_id',
        'total_km', 'km_rate', 'amount', 'payment_status', 'paid_at', 'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'total_km' => 'decimal:2',
            'km_rate'  => 'decimal:2',
            'amount'   => 'decimal:2',
            'paid_at'  => 'datetime',
        ];
    }

    public function tripLog(): BelongsTo
    {
        return $this->belongsTo(TmsTripLog::class, 'trip_log_id');
    }

    public function odometerLog(): BelongsTo
    {
        return $this->belongsTo(TmsDailyOdometerLog::class, 'odometer_log_id');
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TmsVehicle::class, 'vehicle_id');
    }

    public function rentalVendor(): BelongsTo
    {
        return $this->belongsTo(TmsRentalVendor::class, 'rental_vendor_id');
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
