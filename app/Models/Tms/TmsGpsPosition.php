<?php

namespace App\Models\Tms;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsGpsPosition extends Model
{
    protected $table = 'tms_gps_positions';

    protected $fillable = [
        'factory_id', 'vehicle_id', 'trip_log_id',
        'latitude', 'longitude', 'speed_kmh', 'heading', 'accuracy_m',
        'source', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'    => 'decimal:7',
            'longitude'   => 'decimal:7',
            'speed_kmh'   => 'decimal:2',
            'heading'     => 'decimal:2',
            'accuracy_m'  => 'decimal:2',
            'recorded_at' => 'datetime',
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

    public function tripLog(): BelongsTo
    {
        return $this->belongsTo(TmsTripLog::class, 'trip_log_id');
    }

    public function coordinatesLabel(): string
    {
        return number_format((float) $this->latitude, 6) . ', ' . number_format((float) $this->longitude, 6);
    }

    public function googleMapsUrl(): string
    {
        return 'https://www.google.com/maps?q=' . urlencode((float) $this->latitude . ',' . (float) $this->longitude);
    }
}
