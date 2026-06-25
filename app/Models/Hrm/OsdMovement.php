<?php

namespace App\Models\Hrm;

use App\Models\Hrm\Concerns\BelongsToFactoryEmployee;
use Illuminate\Database\Eloquent\Model;

class OsdMovement extends Model
{
    use BelongsToFactoryEmployee;

    public const TYPES = [
        'official_duty' => 'Official Duty',
        'buyer_visit'   => 'Buyer Visit',
        'training'      => 'Training',
        'other'         => 'Other',
    ];

    public const STATUSES = [
        'pending'   => 'Pending',
        'approved'  => 'Approved',
        'completed' => 'Completed',
        'rejected'  => 'Rejected',
    ];

    protected $table = 'hrm_osd_movements';

    protected $fillable = [
        'factory_id', 'employee_id', 'movement_type', 'start_date', 'end_date',
        'destination', 'purpose', 'status', 'approved_by', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->movement_type] ?? ucfirst($this->movement_type);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
