<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\User;
use App\Support\TmsDriverVehiclePivot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TmsDriver extends Model
{
    use SoftDeletes;

    protected $table = 'tms_drivers';

    protected $fillable = [
        'factory_id', 'employee_id', 'default_vehicle_id', 'license_number', 'ot_rate', 'is_overtime_active',
        'ot_rate_effective_from', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'factory_id'             => 'integer',
            'ot_rate'               => 'decimal:2',
            'is_overtime_active'    => 'boolean',
            'ot_rate_effective_from' => 'date',
        ];
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    /** @param  list<string>  $relations */
    public static function withAssignedVehicles(array $relations): array
    {
        if (TmsDriverVehiclePivot::available()) {
            $relations[] = 'vehicles';
        } else {
            $relations[] = 'defaultVehicle';
        }

        return array_values(array_unique($relations));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function defaultVehicle(): BelongsTo
    {
        return $this->belongsTo(TmsVehicle::class, 'default_vehicle_id');
    }

    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(TmsVehicle::class, 'tms_driver_vehicles', 'driver_id', 'vehicle_id')
            ->withPivot('is_primary')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function primaryVehicleId(): ?int
    {
        if (! TmsDriverVehiclePivot::available()) {
            return $this->default_vehicle_id ? (int) $this->default_vehicle_id : null;
        }

        if ($this->relationLoaded('vehicles')) {
            $primary = $this->vehicles->first(fn (TmsVehicle $vehicle) => (bool) $vehicle->pivot?->is_primary);

            return $primary?->id ?? ($this->default_vehicle_id ? (int) $this->default_vehicle_id : null);
        }

        $primaryId = $this->vehicles()->wherePivot('is_primary', true)->value('tms_vehicles.id');

        return $primaryId ? (int) $primaryId : ($this->default_vehicle_id ? (int) $this->default_vehicle_id : null);
    }

    /** @return list<int> */
    public function assignedVehicleIds(): array
    {
        if (! TmsDriverVehiclePivot::available()) {
            return $this->default_vehicle_id ? [(int) $this->default_vehicle_id] : [];
        }

        if ($this->relationLoaded('vehicles')) {
            $ids = $this->vehicles->pluck('id')->map(fn ($id) => (int) $id)->all();
        } else {
            $ids = $this->vehicles()->pluck('tms_vehicles.id')->map(fn ($id) => (int) $id)->all();
        }

        if ($ids !== []) {
            return $ids;
        }

        return $this->default_vehicle_id ? [(int) $this->default_vehicle_id] : [];
    }

    public function hasAssignedVehicle(int $vehicleId): bool
    {
        return in_array($vehicleId, $this->assignedVehicleIds(), true);
    }

    public function assignedVehiclesLabel(): string
    {
        if (! TmsDriverVehiclePivot::available()) {
            return $this->defaultVehicle?->displayLabel() ?? '—';
        }

        $vehicles = $this->relationLoaded('vehicles')
            ? $this->vehicles
            : $this->vehicles()->orderBy('name')->get();

        if ($vehicles->isEmpty()) {
            return $this->defaultVehicle?->displayLabel() ?? '—';
        }

        return $vehicles
            ->map(function (TmsVehicle $vehicle) {
                $label = $vehicle->displayLabel();

                if ((bool) $vehicle->pivot?->is_primary) {
                    $label .= ' (primary)';
                }

                return $label;
            })
            ->implode(', ');
    }

    public function assignmentSelectLabel(): string
    {
        if (! TmsDriverVehiclePivot::available()) {
            $label = $this->displayLabel();
            $primary = $this->defaultVehicle;

            return $primary ? $label . ' — ' . $primary->displayLabel() : $label . ' — No vehicle';
        }

        $primaryId = $this->primaryVehicleId();
        $vehicles = $this->relationLoaded('vehicles')
            ? $this->vehicles
            : $this->vehicles()->orderBy('name')->get();

        $primary = $vehicles->firstWhere('id', $primaryId) ?? $this->defaultVehicle;
        $label = $this->displayLabel();

        if ($primary) {
            $label .= ' — ' . $primary->displayLabel();
        } else {
            $label .= ' — No vehicle';
        }

        $total = $vehicles->isNotEmpty() ? $vehicles->count() : ($primary ? 1 : 0);

        if ($total > 1) {
            $label .= ' (+' . ($total - 1) . ' more)';
        }

        return $label;
    }

    /** @param  list<int>  $vehicleIds */
    public function syncAssignedVehicles(array $vehicleIds, int $primaryVehicleId): void
    {
        $vehicleIds = array_values(array_unique(array_map('intval', $vehicleIds)));

        if (TmsDriverVehiclePivot::available()) {
            $sync = [];
            foreach ($vehicleIds as $vehicleId) {
                $sync[$vehicleId] = ['is_primary' => $vehicleId === $primaryVehicleId];
            }

            $this->vehicles()->sync($sync);
        }

        $this->update(['default_vehicle_id' => $primaryVehicleId]);
    }

    public function tripLogs(): HasMany
    {
        return $this->hasMany(TmsTripLog::class, 'driver_id');
    }

    public function otRateLogs(): HasMany
    {
        return $this->hasMany(TmsDriverOtRateLog::class, 'driver_id')->latest('id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function displayLabel(): string
    {
        return $this->employee?->name ?? "Driver #{$this->id}";
    }

    public function contactPhone(): ?string
    {
        $phone = trim((string) ($this->employee?->phone ?? ''));

        return $phone !== '' ? $phone : null;
    }
}
