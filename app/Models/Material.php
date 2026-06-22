<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    use HasMasterCode;

    protected $fillable = ['name', 'material_type_id', 'unit', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function codePrefix(): string
    {
        return 'MAT';
    }

    public function materialType(): BelongsTo
    {
        return $this->belongsTo(MaterialType::class);
    }
}
