<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusItem extends Model
{
    protected $table = 'hrm_bonus_items';

    protected $fillable = [
        'bonus_run_id', 'employee_id', 'basic_avg', 'months_worked', 'bonus_amount',
    ];

    protected $casts = [
        'basic_avg'     => 'decimal:2',
        'bonus_amount'  => 'decimal:2',
    ];

    public function bonusRun(): BelongsTo
    {
        return $this->belongsTo(BonusRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
