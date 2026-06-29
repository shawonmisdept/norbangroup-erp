<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TmsRentalVendor extends Model
{
    use SoftDeletes;

    protected $table = 'tms_rental_vendors';

    protected $fillable = [
        'factory_id', 'name', 'contact_person', 'mobile',
        'rental_km_rate', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'rental_km_rate' => 'decimal:2',
        ];
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(TmsVehicle::class, 'rental_vendor_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function displayLabel(): string
    {
        return $this->name;
    }

    public function dropdownLabel(): string
    {
        if ($this->mobile) {
            return $this->name . ' — ' . $this->mobile;
        }

        return $this->name;
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
