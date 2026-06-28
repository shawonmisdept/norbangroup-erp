<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use App\Support\OrgMasterDisplay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasMasterCode;

    protected $fillable = ['name', 'native_name', 'factory_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function codePrefix(): string
    {
        return 'DEP';
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function displayLabel(): string
    {
        return OrgMasterDisplay::department($this);
    }
}
