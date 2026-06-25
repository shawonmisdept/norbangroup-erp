<?php

namespace App\Models\Hrm;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;

class EmploymentType extends Model
{
    use HasMasterCode;

    protected $table = 'hrm_employment_types';

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function codePrefix(): string
    {
        return 'EMP';
    }
}
