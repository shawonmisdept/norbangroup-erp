<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryRecord extends Model
{
    public const STATUSES = [
        'open'   => 'Open',
        'closed' => 'Closed',
    ];

    protected $table = 'hrm_disciplinary_records';

    protected $fillable = [
        'factory_id', 'employee_id', 'action_type', 'incident_date',
        'description', 'action_taken', 'suspension_from', 'suspension_to',
        'status', 'recorded_by',
    ];

    protected $casts = [
        'incident_date'   => 'date',
        'suspension_from' => 'date',
        'suspension_to'   => 'date',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function typeLabel(): string
    {
        return config("hrm.disciplinary_types.{$this->action_type}", ucfirst(str_replace('_', ' ', $this->action_type)));
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
