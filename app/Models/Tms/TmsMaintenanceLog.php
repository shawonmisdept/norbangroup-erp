<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmsMaintenanceLog extends Model
{
    protected $table = 'tms_maintenance_logs';

    protected $fillable = [
        'factory_id', 'vehicle_id', 'service_date', 'odometer_km', 'vendor_name',
        'service_type', 'description', 'labor_cost', 'parts_cost', 'total_cost',
        'paid_by', 'status', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'odometer_km'  => 'decimal:2',
            'labor_cost'   => 'decimal:2',
            'parts_cost'   => 'decimal:2',
            'total_cost'   => 'decimal:2',
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

    public function parts(): HasMany
    {
        return $this->hasMany(TmsMaintenancePart::class, 'maintenance_log_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function serviceTypeLabel(): string
    {
        return config("tms.maintenance_service_types.{$this->service_type}", ucfirst((string) $this->service_type));
    }

    public function statusLabel(): string
    {
        return config("tms.maintenance_statuses.{$this->status}", ucfirst((string) $this->status));
    }

    public function statusBadgeClass(): string
    {
        return config("tms.maintenance_status_colors.{$this->status}", 'bg-gray-100 text-gray-600');
    }
}
