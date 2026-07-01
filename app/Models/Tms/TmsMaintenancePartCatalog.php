<?php

namespace App\Models\Tms;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmsMaintenancePartCatalog extends Model
{
    protected $table = 'tms_maintenance_part_catalog';

    protected $fillable = [
        'factory_id', 'name', 'unit', 'default_unit_price', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_unit_price' => 'decimal:2',
            'is_active'          => 'boolean',
        ];
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(TmsMaintenanceItem::class, 'part_catalog_id');
    }

    public function displayLabel(): string
    {
        $label = $this->name;
        if ($this->unit) {
            $label .= " ({$this->unit})";
        }

        return $label;
    }
}
