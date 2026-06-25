<?php

namespace App\Models\Hrm;

use App\Models\Hrm\Concerns\BelongsToFactoryEmployee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryHold extends Model
{
    use BelongsToFactoryEmployee;

    public const STATUSES = [
        'active'   => 'Active',
        'released' => 'Released',
    ];

    protected $table = 'hrm_salary_holds';

    protected $fillable = [
        'factory_id', 'employee_id', 'payroll_period_id', 'reason',
        'hold_from', 'hold_until', 'status', 'released_by', 'released_at', 'created_by',
    ];

    protected $casts = [
        'hold_from'   => 'date',
        'hold_until'  => 'date',
        'released_at' => 'datetime',
    ];

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isActiveOn(string $date): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->hold_from->toDateString() > $date) {
            return false;
        }

        return ! $this->hold_until || $this->hold_until->toDateString() >= $date;
    }
}
