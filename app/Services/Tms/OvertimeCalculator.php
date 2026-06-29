<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsTripLog;

/** @deprecated Use DriverPayCalculator — kept for backward compatibility */
class OvertimeCalculator
{
    public function __construct(
        private DriverPayCalculator $driverPayCalculator,
    ) {}

    /** @return array{ot_hours: float, ot_amount: float, ot_start_at: ?\Carbon\Carbon, ot_end_at: ?\Carbon\Carbon} */
    public function calculate(TmsTripLog $tripLog): array
    {
        $pay = $this->driverPayCalculator->calculate($tripLog);

        return [
            'ot_hours'    => $pay['ot_hours'],
            'ot_amount'   => $pay['ot_amount'],
            'ot_start_at' => $pay['ot_start_at'],
            'ot_end_at'   => $pay['ot_end_at'],
        ];
    }
}
