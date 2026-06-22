<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyCalendar extends Model
{
    use HasMasterCode;

    protected $fillable = [
        'name', 'calendar_type', 'start_date', 'end_date',
        'description', 'factory_id', 'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public static function codePrefix(): string
    {
        return 'CAL';
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
}
