<?php

namespace App\Models\Hrm;

use App\Models\Hrm\Concerns\BelongsToFactoryEmployee;
use Illuminate\Database\Eloquent\Model;

class MedicalVisit extends Model
{
    use BelongsToFactoryEmployee;

    protected $table = 'hrm_medical_visits';

    protected $fillable = [
        'factory_id', 'employee_id', 'visit_date', 'complaint', 'diagnosis',
        'treatment', 'referred', 'notes', 'created_by',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'referred'   => 'boolean',
    ];
}
