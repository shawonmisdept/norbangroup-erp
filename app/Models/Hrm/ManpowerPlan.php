<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManpowerPlan extends Model
{
    protected $table = 'hrm_manpower_plans';

    protected $fillable = [
        'factory_id', 'line_id', 'plan_date', 'required_count', 'notes', 'created_by',
    ];

    protected $casts = [
        'plan_date' => 'date',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class);
    }
}
