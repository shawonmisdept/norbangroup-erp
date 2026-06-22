<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasMasterCode;

    protected $fillable = [
        'name', 'branch', 'account_name', 'account_number',
        'routing_number', 'swift_code', 'country', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public static function codePrefix(): string
    {
        return 'BNK';
    }
}
