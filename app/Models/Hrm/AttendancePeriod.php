<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePeriod extends Model
{
    public const STATUSES = [
        'draft'     => 'Draft',
        'processed' => 'Processed',
        'frozen'    => 'Frozen',
    ];

    protected $table = 'hrm_attendance_periods';

    protected $fillable = [
        'factory_id', 'year', 'month', 'start_date', 'end_date',
        'status', 'processed_at', 'frozen_at', 'processed_by', 'notes',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'processed_at' => 'datetime',
        'frozen_at'    => 'datetime',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(AttendanceDailyLog::class, 'attendance_period_id');
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isFrozen(): bool
    {
        return $this->status === 'frozen';
    }

    public function isEditable(): bool
    {
        return $this->status !== 'frozen';
    }

    public function periodLabel(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    public static function getOrCreateForMonth(int $factoryId, int $year, int $month): self
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return static::firstOrCreate(
            ['factory_id' => $factoryId, 'year' => $year, 'month' => $month],
            [
                'start_date' => $start->toDateString(),
                'end_date'   => $end->toDateString(),
                'status'     => 'draft',
            ]
        );
    }
}
