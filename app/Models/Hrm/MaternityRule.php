<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaternityRule extends Model
{
    protected $table = 'hrm_maternity_rules';

    protected $fillable = [
        'factory_id', 'total_weeks', 'paid_weeks', 'unpaid_weeks',
        'min_service_days', 'notes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
}
