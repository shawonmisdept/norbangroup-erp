<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRule extends Model
{
    public const GENDERS = [
        'male'   => 'Male only',
        'female' => 'Female only',
    ];

    protected $table = 'hrm_leave_rules';

    protected $fillable = [
        'factory_id', 'leave_type_id', 'worker_category_id', 'employment_type_id',
        'min_tenure_days', 'gender', 'allow_probation', 'notes', 'is_active',
    ];

    protected $casts = [
        'allow_probation' => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function workerCategory(): BelongsTo
    {
        return $this->belongsTo(WorkerCategory::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }
}
