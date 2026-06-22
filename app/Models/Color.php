<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasMasterCode;

    protected $fillable = ['name', 'hex_code', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function codePrefix(): string
    {
        return 'CLR';
    }
}
