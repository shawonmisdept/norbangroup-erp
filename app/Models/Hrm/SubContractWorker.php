<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubContractWorker extends Model
{
    public const STATUSES = [
        'active'   => 'Active',
        'inactive' => 'Inactive',
    ];

    protected $table = 'hrm_sub_contract_workers';

    protected $fillable = [
        'factory_id', 'line_id', 'agency_name', 'name', 'phone', 'nid_number',
        'start_date', 'end_date', 'status', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
