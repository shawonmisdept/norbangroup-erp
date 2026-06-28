<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factory extends Model
{
    use HasMasterCode;

    protected $fillable = [
        'name', 'native_name', 'address', 'phone', 'is_active',
        'attendance_lat', 'attendance_lng', 'attendance_radius_m', 'mobile_checkin_enabled',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'mobile_checkin_enabled' => 'boolean',
    ];

    public static function codePrefix(): string
    {
        return 'FAC';
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
