<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasMasterCode;

    protected $fillable = ['name', 'year', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'is_active'  => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public static function codePrefix(): string
    {
        return 'SEA';
    }
}
