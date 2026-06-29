<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TmsVehicle extends Model
{
    use SoftDeletes;

    protected $table = 'tms_vehicles';

    protected $fillable = [
        'factory_id', 'name', 'reg_number', 'type', 'fuel_type', 'passenger_capacity',
        'status', 'rental_vendor_id', 'rental_km_rate', 'fuel_covered_by',
        'maintenance_covered_by', 'allocated_employee_id', 'last_odometer_km', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'passenger_capacity' => 'integer',
            'rental_km_rate'   => 'decimal:2',
            'last_odometer_km' => 'decimal:2',
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

    public function tripLogs(): HasMany
    {
        return $this->hasMany(TmsTripLog::class, 'vehicle_id');
    }

    public function defaultDrivers(): HasMany
    {
        return $this->hasMany(TmsDriver::class, 'default_vehicle_id');
    }

    public function assignedDriverNames(): string
    {
        $drivers = $this->relationLoaded('defaultDrivers')
            ? $this->defaultDrivers
            : $this->defaultDrivers()->with('employee')->where('status', 'active')->get();

        $names = $drivers
            ->where('status', 'active')
            ->map(fn (TmsDriver $driver) => $driver->displayLabel())
            ->filter()
            ->unique()
            ->values();

        return $names->isNotEmpty() ? $names->implode(', ') : '—';
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
