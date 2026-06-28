<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsFuelLog extends Model
{
    protected $table = 'tms_fuel_logs';

    protected $fillable = [
        'factory_id', 'vehicle_id', 'trip_log_id', 'fuel_type', 'quantity', 'unit',
        'unit_price', 'amount', 'receipt_number', 'receipt_path', 'paid_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'decimal:3',
            'unit_price' => 'decimal:2',
            'amount'     => 'decimal:2',
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
}
