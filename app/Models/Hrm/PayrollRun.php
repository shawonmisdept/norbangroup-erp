<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    public const STATUSES = [
        'running'   => 'Running',
        'completed' => 'Completed',
        'failed'    => 'Failed',
    ];

    protected $table = 'hrm_payroll_runs';

    protected $fillable = [
        'payroll_period_id', 'status', 'employee_count',
        'started_at', 'completed_at', 'run_by', 'notes',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function runByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'run_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}
