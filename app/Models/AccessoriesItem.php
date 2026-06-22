<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;

class AccessoriesItem extends Model
{
    use HasMasterCode;

    protected $fillable = ['name', 'description', 'unit', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function codePrefix(): string
    {
        return 'ACI';
    }
}
