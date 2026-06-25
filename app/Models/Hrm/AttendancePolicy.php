<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePolicy extends Model
{
    protected $table = 'hrm_attendance_policies';

    protected $fillable = [
        'factory_id',
        'late_grace_minutes',
        'consecutive_late_grace_days',
        'late_deduction_basis',
        'late_streak_resets_on_absent',
        'early_leave_grace_minutes',
        'min_half_day_minutes',
        'full_day_minutes',
        'max_monthly_ot_hours',
        'ot_multiplier_normal',
        'ot_multiplier_holiday',
        'ot_multiplier_night',
        'max_daily_hours',
        'max_weekly_hours',
        'min_employment_age',
        'default_half_day_pay_ratio',
        'is_active',
    ];

    protected $casts = [
        'is_active'                   => 'boolean',
        'late_streak_resets_on_absent'=> 'boolean',
        'default_half_day_pay_ratio'  => 'decimal:2',
        'ot_multiplier_normal'        => 'decimal:2',
        'ot_multiplier_holiday'       => 'decimal:2',
        'ot_multiplier_night'         => 'decimal:2',
        'max_daily_hours'             => 'decimal:1',
        'max_weekly_hours'            => 'decimal:1',
    ];

    public const LATE_DEDUCTION_BASES = [
        'basic' => 'Basic Salary / 26',
        'gross' => 'Gross Salary / 26',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public static function forFactory(int $factoryId): self
    {
        return static::firstOrCreate(
            ['factory_id' => $factoryId],
            [
                'late_grace_minutes'          => 10,
                'consecutive_late_grace_days' => 3,
                'late_deduction_basis'        => 'basic',
                'late_streak_resets_on_absent'=> true,
                'early_leave_grace_minutes'   => 10,
                'min_half_day_minutes'        => 240,
                'full_day_minutes'            => 480,
                'max_monthly_ot_hours'        => 104,
                'ot_multiplier_normal'        => 2.0,
                'ot_multiplier_holiday'       => 2.0,
                'ot_multiplier_night'         => 2.0,
                'max_daily_hours'             => 10.0,
                'max_weekly_hours'            => 60.0,
                'min_employment_age'          => 18,
                'default_half_day_pay_ratio'    => 0.5,
                'is_active'                   => true,
            ]
        );
    }
}
