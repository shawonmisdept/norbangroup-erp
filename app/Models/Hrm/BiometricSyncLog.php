<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricSyncLog extends Model
{
    protected $table = 'hrm_biometric_sync_logs';

    protected $fillable = [
        'biometric_device_id', 'status',
        'records_fetched', 'records_imported', 'records_skipped',
        'message', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function biometricDevice(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class);
    }
}
