<?php

namespace App\Models\Hrm;

use App\Models\Hrm\Concerns\BelongsToFactoryEmployee;
use Illuminate\Database\Eloquent\Model;

class GatePass extends Model
{
    use BelongsToFactoryEmployee;

    public const STATUSES = [
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'used'     => 'Used',
    ];

    protected $table = 'hrm_gate_passes';

    protected $fillable = [
        'factory_id', 'employee_id', 'pass_date', 'out_time', 'expected_in_time',
        'destination', 'reason', 'status', 'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'pass_date'   => 'date',
        'approved_at' => 'datetime',
    ];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
