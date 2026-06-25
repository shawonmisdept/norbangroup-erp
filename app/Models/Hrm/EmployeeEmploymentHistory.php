<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEmploymentHistory extends Model
{
    protected $table = 'hrm_employee_employment_histories';

    protected $fillable = [
        'employee_id',
        'company_name',
        'designation',
        'department',
        'joining_date',
        'leaving_date',
        'reason_for_leaving',
        'sort_order',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'leaving_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
