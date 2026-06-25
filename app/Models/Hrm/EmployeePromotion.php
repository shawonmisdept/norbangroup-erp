<?php

namespace App\Models\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePromotion extends Model
{
    public const STATUSES = [
        'pending'   => 'Pending',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    public const MOVEMENT_TYPES = [
        'promotion' => 'Promotion',
        'demotion'  => 'Demotion',
    ];

    protected $table = 'hrm_employee_promotions';

    protected $fillable = [
        'factory_id', 'employee_id', 'movement_type', 'status',
        'from_designation_id', 'to_designation_id',
        'from_department_id', 'to_department_id',
        'from_worker_category_id', 'to_worker_category_id',
        'from_reporting_to_id', 'to_reporting_to_id',
        'from_salary_grade_id', 'to_salary_grade_id',
        'from_gross_salary', 'to_gross_salary', 'apply_salary_change',
        'effective_date', 'reason', 'remarks',
        'created_by', 'approved_by', 'rejected_by',
        'approved_at', 'rejected_at', 'rejection_reason',
    ];

    protected $casts = [
        'effective_date'      => 'date',
        'from_gross_salary'   => 'decimal:2',
        'to_gross_salary'     => 'decimal:2',
        'apply_salary_change' => 'boolean',
        'approved_at'         => 'datetime',
        'rejected_at'         => 'datetime',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function fromDesignation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'from_designation_id');
    }

    public function toDesignation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'to_designation_id');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromWorkerCategory(): BelongsTo
    {
        return $this->belongsTo(WorkerCategory::class, 'from_worker_category_id');
    }

    public function toWorkerCategory(): BelongsTo
    {
        return $this->belongsTo(WorkerCategory::class, 'to_worker_category_id');
    }

    public function fromReportingTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'from_reporting_to_id');
    }

    public function toReportingTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'to_reporting_to_id');
    }

    public function fromSalaryGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class, 'from_salary_grade_id');
    }

    public function toSalaryGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class, 'to_salary_grade_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function movementTypeLabel(): string
    {
        return self::MOVEMENT_TYPES[$this->movement_type] ?? ucfirst($this->movement_type);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
