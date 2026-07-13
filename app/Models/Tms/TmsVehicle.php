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

class TmsVehicle extends Model
{
    use SoftDeletes;

    protected $table = 'tms_vehicles';

    protected $fillable = [
        'factory_id', 'name', 'vehicle_category', 'model_year', 'engine_cc', 'reg_number', 'type', 'fuel_type',
        'passenger_capacity', 'status', 'rental_vendor_id', 'rental_km_rate', 'fuel_covered_by',
        'maintenance_covered_by', 'purchase_date', 'registration_date', 'purchase_value', 'is_dedicated',
        'fitness_expires_at', 'tax_token_expires_at', 'insurance_expires_at', 'route_permit_expires_at',
        'registration_paper_status', 'allocated_employee_id', 'primary_driver_id', 'last_odometer_km',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'passenger_capacity'        => 'integer',
            'factory_id'                => 'integer',
            'model_year'                => 'integer',
            'engine_cc'                 => 'integer',
            'rental_km_rate'            => 'decimal:2',
            'purchase_value'            => 'decimal:2',
            'last_odometer_km'          => 'decimal:2',
            'is_dedicated'              => 'boolean',
            'purchase_date'             => 'date',
            'registration_date'       => 'date',
            'fitness_expires_at'        => 'date',
            'tax_token_expires_at'      => 'date',
            'insurance_expires_at'      => 'date',
            'route_permit_expires_at'   => 'date',
        ];
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function rentalVendor(): BelongsTo
    {
        return $this->belongsTo(TmsRentalVendor::class, 'rental_vendor_id');
    }

    public function allocatedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'allocated_employee_id');
    }

    public function primaryDriver(): BelongsTo
    {
        return $this->belongsTo(TmsDriver::class, 'primary_driver_id');
    }

    public function paperRenewals(): HasMany
    {
        return $this->hasMany(TmsVehiclePaperRenewal::class, 'vehicle_id')->latest('renewed_at');
    }

    public function tripLogs(): HasMany
    {
        return $this->hasMany(TmsTripLog::class, 'vehicle_id');
    }

    public function defaultDrivers(): HasMany
    {
        return $this->hasMany(TmsDriver::class, 'default_vehicle_id');
    }

    public function assignedCompanyDrivers(): BelongsToMany
    {
        return $this->belongsToMany(TmsDriver::class, 'tms_driver_vehicles', 'vehicle_id', 'driver_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function assignedDriverNames(): string
    {
        if ($this->relationLoaded('primaryDriver') && $this->primaryDriver?->isActive()) {
            return $this->primaryDriver->displayLabel();
        }

        if ($this->primary_driver_id) {
            $primary = $this->primaryDriver()->with('employee')->first();
            if ($primary?->isActive()) {
                return $primary->displayLabel();
            }
        }

        $drivers = collect();

        if (TmsDriverVehiclePivot::available()) {
            $pivotDrivers = $this->relationLoaded('assignedCompanyDrivers')
                ? $this->assignedCompanyDrivers
                : $this->assignedCompanyDrivers()->with('employee')->where('status', 'active')->get();

            $drivers = $drivers->merge($pivotDrivers->where('status', 'active'));
        }

        $legacyDrivers = $this->relationLoaded('defaultDrivers')
            ? $this->defaultDrivers
            : $this->defaultDrivers()->with('employee')->where('status', 'active')->get();

        $drivers = $drivers->merge($legacyDrivers->where('status', 'active'));

        $names = $drivers
            ->map(fn (TmsDriver $driver) => $driver->displayLabel())
            ->filter()
            ->unique()
            ->values();

        return $names->isNotEmpty() ? $names->implode(', ') : '—';
    }

    public function primaryDriverContact(): ?string
    {
        $driver = $this->relationLoaded('primaryDriver')
            ? $this->primaryDriver
            : ($this->primary_driver_id ? $this->primaryDriver()->with('employee')->first() : null);

        return $driver?->contactPhone();
    }

    public function isRental(): bool
    {
        return $this->type === 'rental';
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function displayLabel(): string
    {
        return "{$this->name} ({$this->reg_number})";
    }

    public function regNumberSuffix(): string
    {
        $parts = preg_split('/[\s\-]+/', trim((string) $this->reg_number)) ?: [];

        return (string) (array_pop($parts) ?: $this->reg_number);
    }

    public function postingCarNoLabel(): string
    {
        $suffix = $this->regNumberSuffix();

        if ($this->isRental() && $this->rentalVendor) {
            return "{$this->rentalVendor->name} Car No: {$suffix}";
        }

        return "Company Car No: {$suffix}";
    }

    public function allocatedUserLabel(): ?string
    {
        $employee = $this->allocatedEmployee;

        if (! $employee) {
            return null;
        }

        $designation = $employee->designation?->name;

        return $designation
            ? "{$employee->name} ({$designation})"
            : $employee->name;
    }

    public function maintenanceBills(): HasMany
    {
        return $this->hasMany(TmsMaintenanceBill::class, 'vehicle_id');
    }

    public function statusLabel(): string
    {
        return config("tms.vehicle_statuses.{$this->status}", ucfirst((string) $this->status));
    }

    public function statusBadgeClass(): string
    {
        return config("tms.vehicle_status_colors.{$this->status}", 'bg-gray-100 text-gray-600');
    }
}
