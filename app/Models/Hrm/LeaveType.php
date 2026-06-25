<?php

namespace App\Models\Hrm;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasMasterCode;

    protected $table = 'hrm_leave_types';

    protected $fillable = ['code', 'name', 'is_paid', 'max_days_per_year', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_paid' => 'boolean',
        'max_days_per_year' => 'integer',
    ];

    public static function codePrefix(): string
    {
        return 'LVT';
    }
}
