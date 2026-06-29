<?php

namespace App\Models\Tms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsMaintenancePart extends Model
{
    protected $table = 'tms_maintenance_parts';

    protected $fillable = [
        'maintenance_log_id', 'part_name', 'quantity', 'unit_price', 'amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'decimal:3',
            'unit_price' => 'decimal:2',
            'amount'     => 'decimal:2',
        ];
    }

    public function maintenanceLog(): BelongsTo
    {
        return $this->belongsTo(TmsMaintenanceLog::class, 'maintenance_log_id');
    }
}
