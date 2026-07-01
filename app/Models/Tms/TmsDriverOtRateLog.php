<?php

namespace App\Models\Tms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsDriverOtRateLog extends Model
{
    protected $table = 'tms_driver_ot_rate_logs';

    protected $fillable = [
        'driver_id', 'ot_rate', 'effective_from', 'is_overtime_active', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'ot_rate'            => 'decimal:2',
            'effective_from'     => 'date',
            'is_overtime_active' => 'boolean',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TmsDriver::class, 'driver_id');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
