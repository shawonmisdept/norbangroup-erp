<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TmsTransportRequest extends Model
{
    protected $table = 'tms_transport_requests';

    protected $fillable = [
        'factory_id', 'employee_id', 'pickup_location', 'destination_id', 'destination_custom',
        'pickup_at', 'purpose', 'passenger_count', 'status', 'vehicle_id', 'driver_id', 'trip_log_id',
        'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'pickup_at'       => 'datetime',
            'approved_at'     => 'datetime',
            'rejected_at'     => 'datetime',
            'cancelled_at'    => 'datetime',
            'passenger_count' => 'integer',
        ];
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(TmsDestination::class, 'destination_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TmsVehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TmsDriver::class, 'driver_id');
    }

    public function tripLog(): BelongsTo
    {
        return $this->belongsTo(TmsTripLog::class, 'trip_log_id');
    }

    /** @deprecated use tripLog() — kept for legacy rows */
    public function legacyTripLog(): HasOne
    {
        return $this->hasOne(TmsTripLog::class, 'transport_request_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TmsTransportRequestHistory::class, 'transport_request_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function destinationLabel(): string
    {
        if ($this->destination) {
            return $this->destination->name;
        }

        return $this->destination_custom ?? '—';
    }

    public function statusLabel(): string
    {
        return config("tms.request_statuses.{$this->status}", ucfirst($this->status));
    }

    public function statusBadgeClass(): string
    {
        return config("tms.request_status_colors.{$this->status}", 'bg-gray-100 text-gray-600');
    }

    public function canBeCancelledByEmployee(): bool
    {
        if ($this->status === 'pending') {
            return true;
        }

        if ($this->status !== 'approved') {
            return false;
        }

        $trip = $this->relationLoaded('tripLog') ? $this->tripLog : $this->tripLog()->first();

        return $trip?->trip_status === 'not_started';
    }
}
