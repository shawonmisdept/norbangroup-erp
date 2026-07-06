<?php

namespace App\Models\Hrm;

use App\Models\Concerns\HasMasterCode;
use App\Models\Factory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasMasterCode;

    protected $table = 'hrm_shifts';

    protected $fillable = [
        'factory_id', 'name', 'start_time', 'end_time',
        'break_minutes', 'break_start_time', 'break_end_time',
        'is_night', 'description', 'is_active',
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

    protected static function booted(): void
    {
        static::saving(function (Shift $shift) {
            if ($shift->break_start_time && $shift->break_end_time) {
                $start = Carbon::parse($shift->break_start_time);
                $end = Carbon::parse($shift->break_end_time);

                if ($end->lessThanOrEqualTo($start)) {
                    $end = $end->copy()->addDay();
                }

                $shift->break_minutes = (int) $start->diffInMinutes($end);
            }
        });
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function displayLabel(): string
    {
        return \App\Support\OrgMasterDisplay::shift($this);
    }
}
