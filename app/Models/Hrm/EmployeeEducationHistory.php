<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEducationHistory extends Model
{
    protected $table = 'hrm_employee_education_histories';

    protected $fillable = [
        'employee_id',
        'degree',
        'institution',
        'board_or_university',
        'passing_year',
        'result',
        'sort_order',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
