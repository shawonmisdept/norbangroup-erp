<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class TmsRentalDriver extends Model
{
    use SoftDeletes;

    protected $table = 'tms_rental_drivers';

    protected $fillable = [
        'factory_id', 'name', 'mobile', 'nid_number', 'license_number', 'photo',
        'rental_vendor_id', 'vendor_name', 'vendor_contact', 'default_vehicle_id', 'status', 'notes',
        'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::deleting(function (TmsRentalDriver $driver) {
            if ($driver->photo) {
                Storage::disk('public')->delete($driver->photo);
            }
        });
    }

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

    public function contactPhone(): ?string
    {
        $phone = trim((string) ($this->mobile ?? ''));

        return $phone !== '' ? $phone : null;
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

    public function photoUrl(): ?string
    {
        return $this->photo
            ? Storage::disk('public')->url($this->photo)
            : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return strtoupper(collect($parts)->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    }
}
