<?php

namespace App\Models\Hrm;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;

class WorkerCategory extends Model
{
    use HasMasterCode;

    protected $table = 'hrm_worker_categories';

    protected $fillable = ['name', 'native_name', 'description', 'minimum_wage', 'is_active'];

    protected $casts = [
        'is_active'     => 'boolean',
        'minimum_wage'  => 'decimal:2',
    ];

    public static function codePrefix(): string
    {
        return 'WCT';
    }
}
