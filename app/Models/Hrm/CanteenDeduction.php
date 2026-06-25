<?php

namespace App\Models\Hrm;

use App\Models\Hrm\Concerns\BelongsToFactoryEmployee;
use Illuminate\Database\Eloquent\Model;

class CanteenDeduction extends Model
{
    use BelongsToFactoryEmployee;

    protected $table = 'hrm_canteen_deductions';

    protected $fillable = [
        'factory_id', 'employee_id', 'period_year', 'period_month',
        'meal_count', 'amount', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
