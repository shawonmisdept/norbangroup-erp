<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsSetting extends Model
{
    protected $table = 'tms_settings';

    protected $fillable = [
        'factory_id', 'office_start', 'office_end', 'ot_basis',
        'company_night_bill', 'company_holiday_duty_bill',
        'rental_ot_hourly_rate', 'rental_km_rate', 'weekend_days',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'company_night_bill'        => 'decimal:2',
            'company_holiday_duty_bill' => 'decimal:2',
            'rental_ot_hourly_rate'     => 'decimal:2',
            'rental_km_rate'            => 'decimal:2',
            'weekend_days'              => 'array',
        ];
    }

    /** @return array<string, mixed> */
    public static function defaultValues(): array
    {
        return [
            'office_start'                => '09:00:00',
            'office_end'                  => '17:00:00',
            'ot_basis'                    => 'global_office_time',
            'company_night_bill'          => 120,
            'company_holiday_duty_bill'   => 320,
            'rental_ot_hourly_rate'      => 120,
            'rental_km_rate'              => 12,
            'weekend_days'                => [5, 6],
        ];
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
