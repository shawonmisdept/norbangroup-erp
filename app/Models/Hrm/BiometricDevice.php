<?php

namespace App\Models\Hrm;

use App\Models\Concerns\HasMasterCode;
use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricDevice extends Model
{
    use HasMasterCode;

    protected $table = 'hrm_biometric_devices';

    protected $fillable = [
        'factory_id', 'name', 'device_serial', 'device_model', 'ip_address',
        'adms_url', 'location', 'description', 'is_active',
        'last_synced_at', 'last_sync_status', 'last_sync_message', 'last_seen_at',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
        'last_seen_at'   => 'datetime',
    ];

    public static function codePrefix(): string
    {
        return 'BIO';
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(BiometricSyncLog::class);
    }

    public function rawPunches(): HasMany
    {
        return $this->hasMany(AttendanceRawPunch::class);
    }

    public function hasAdmsEndpoint(): bool
    {
        return filled($this->adms_url);
    }
}
