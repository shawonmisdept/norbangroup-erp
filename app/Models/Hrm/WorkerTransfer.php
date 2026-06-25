<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerTransfer extends Model
{
    public const STATUSES = [
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    protected $table = 'hrm_worker_transfers';

    protected $fillable = [
        'factory_id', 'employee_id', 'to_factory_id', 'to_line_id', 'to_floor_id',
        'to_building_id', 'effective_date', 'reason', 'status',
        'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'approved_at'    => 'datetime',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function toFactory(): BelongsTo
    {
        return $this->belongsTo(Factory::class, 'to_factory_id');
    }

    public function toLine(): BelongsTo
    {
        return $this->belongsTo(Line::class, 'to_line_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
