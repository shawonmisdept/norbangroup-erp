<?php

namespace App\Models\Hrm;

use App\Models\Concerns\HasMasterCode;
use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    use HasMasterCode;

    protected $table = 'hrm_floors';

    protected $fillable = ['factory_id', 'building_id', 'name', 'floor_number', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'floor_number' => 'integer',
    ];

    public static function codePrefix(): string
    {
        return 'FLR';
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(Line::class, 'floor_id');
    }
}
