<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buyer extends Model
{
    use HasMasterCode;

    protected $fillable = ['name', 'company', 'email', 'phone', 'country', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function codePrefix(): string
    {
        return 'BUY';
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function buyerClasses(): HasMany
    {
        return $this->hasMany(BuyerClass::class);
    }
}
