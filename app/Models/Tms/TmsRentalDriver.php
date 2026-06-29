<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TmsRentalDriver extends Model
{
    use SoftDeletes;

    protected $table = 'tms_rental_drivers';

    protected $fillable = [
        'factory_id', 'name', 'mobile', 'nid_number', 'license_number',
        'rental_vendor_id', 'vendor_name', 'vendor_contact', 'default_vehicle_id', 'status', 'notes',
        'created_by', 'updated_by',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function defaultVehicle(): BelongsTo
    {
        return $this->belongsTo(TmsVehicle::class, 'default_vehicle_id');
    }

    public function rentalVendor(): BelongsTo
    {
        return $this->belongsTo(TmsRentalVendor::class, 'rental_vendor_id');
    }

    public function tripLogs(): HasMany
    {
        return $this->hasMany(TmsTripLog::class, 'rental_driver_id');
    }

    public function portalUser(): HasOne
    {
        return $this->hasOne(TmsRentalDriverPortalUser::class, 'rental_driver_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function displayLabel(): string
    {
        $label = $this->name;

        if ($this->mobile) {
            $label .= ' (' . $this->mobile . ')';
        }

        return $label;
    }

    public function vendorLabel(): string
    {
        if ($this->relationLoaded('rentalVendor') || $this->rental_vendor_id) {
            $vendor = $this->rentalVendor;

            if ($vendor) {
                return $vendor->dropdownLabel();
            }
        }

        if ($this->vendor_name && $this->vendor_contact) {
            return $this->vendor_name . ' — ' . $this->vendor_contact;
        }

        return $this->vendor_name ?? '—';
    }
}
