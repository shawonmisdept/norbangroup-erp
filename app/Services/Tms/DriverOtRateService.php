<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsDriverOtRateLog;

class DriverOtRateService
{
    public function record(TmsDriver $driver, ?int $userId = null): TmsDriverOtRateLog
    {
        return TmsDriverOtRateLog::create([
            'driver_id'          => $driver->id,
            'ot_rate'            => $driver->ot_rate,
            'effective_from'     => $driver->ot_rate_effective_from,
            'is_overtime_active' => $driver->is_overtime_active,
            'recorded_by'        => $userId,
        ]);
    }

    public function recordIfPayRulesChanged(TmsDriver $driver, TmsDriver $before, ?int $userId = null): ?TmsDriverOtRateLog
    {
        $rateChanged = (float) $before->ot_rate !== (float) $driver->ot_rate;
        $fromChanged = ($before->ot_rate_effective_from?->toDateString() ?? null)
            !== ($driver->ot_rate_effective_from?->toDateString() ?? null);
        $activeChanged = (bool) $before->is_overtime_active !== (bool) $driver->is_overtime_active;

        if (! $rateChanged && ! $fromChanged && ! $activeChanged) {
            return null;
        }

        return $this->record($driver, $userId);
    }
}
