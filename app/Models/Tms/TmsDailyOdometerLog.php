<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsDailyOdometerLog extends Model
{
    protected $table = 'tms_daily_odometer_logs';

    protected $fillable = [
        'factory_id', 'vehicle_id', 'log_date', 'morning_km', 'evening_km', 'notes',
        'morning_entered_by', 'evening_entered_by',
    ];

    protected function casts(): array
    {
        return [
            'log_date'   => 'date',
            'morning_km' => 'decimal:2',
            'evening_km' => 'decimal:2',
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

    public function dailyKm(): ?float
    {
        if ($this->morning_km !== null && $this->evening_km !== null) {
            return round((float) $this->evening_km - (float) $this->morning_km, 2);
        }

        return null;
    }
}
