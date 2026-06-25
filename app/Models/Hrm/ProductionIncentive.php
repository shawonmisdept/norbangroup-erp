<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionIncentive extends Model
{
    public const STATUSES = [
        'draft'    => 'Draft',
        'approved' => 'Approved',
        'paid'     => 'Paid',
    ];

    protected $table = 'hrm_production_incentives';

    protected $fillable = [
        'factory_id', 'line_id', 'period_year', 'period_month', 'output_qty',
        'incentive_rate', 'total_amount', 'status', 'notes', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'incentive_rate' => 'decimal:2',
        'total_amount'   => 'decimal:2',
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
