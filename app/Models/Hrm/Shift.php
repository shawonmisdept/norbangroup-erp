<?php

namespace App\Models\Hrm;

use App\Models\Concerns\HasMasterCode;
use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasMasterCode;

    protected $table = 'hrm_shifts';

    protected $fillable = [
        'factory_id', 'name', 'start_time', 'end_time',
        'break_minutes', 'is_night', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_night' => 'boolean',
        'break_minutes' => 'integer',
    ];

    public static function codePrefix(): string
    {
        return 'SFT';
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
}
