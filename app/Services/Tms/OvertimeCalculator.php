<?php

namespace App\Services\Tms;

use App\Models\Hrm\Employee;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTripLog;
use Carbon\Carbon;

class OvertimeCalculator
{
    /** @return array{ot_hours: float, ot_amount: float, ot_start_at: ?Carbon, ot_end_at: ?Carbon} */
    public function calculate(TmsTripLog $tripLog): array
    {
        $tripLog->loadMissing([
            'driver.employee.shift',
            'transportRequests',
            'transportRequest',
        ]);

        $driver = $tripLog->driver;
        $request = $tripLog->primaryRequest();

        if (! $driver || ! $request || ! $driver->is_overtime_active || ! $tripLog->duty_end_at) {
            return $this->zeroOt();
        }

        $threshold = $this->otThreshold(
            $tripLog->factory_id,
            $driver->employee_id,
            $request->pickup_at
        );

        if (! $threshold || $tripLog->duty_end_at->lte($threshold)) {
            return $this->zeroOt();
        }

        $otHours = round($threshold->diffInMinutes($tripLog->duty_end_at) / 60, 2);
        $rate = (float) $driver->ot_rate;
        $otAmount = round($otHours * $rate, 2);

        return [
            'ot_hours'    => $otHours,
            'ot_amount'   => $otAmount,
            'ot_start_at' => $threshold,
            'ot_end_at'   => $tripLog->duty_end_at,
        ];
    }

    public function otThreshold(int $factoryId, int $employeeId, Carbon $pickupAt): ?Carbon
    {
        $settings = TmsSetting::where('factory_id', $factoryId)->first();
        $basis = $settings?->ot_basis ?? 'global_office_time';

        if ($basis === 'employee_shift_end') {
            $employee = Employee::with('shift')->find($employeeId);
            $endTime = $employee?->shift?->end_time;

            if ($endTime) {
                return $this->combineDateAndTime($pickupAt, $endTime);
            }
        }

        $officeEnd = $settings?->office_end ?? '17:00:00';

        return $this->combineDateAndTime($pickupAt, $officeEnd);
    }

    private function combineDateAndTime(Carbon $date, mixed $time): Carbon
    {
        $timeStr = $time instanceof Carbon ? $time->format('H:i:s') : (string) $time;

        return Carbon::parse($date->toDateString() . ' ' . $timeStr);
    }

    /** @return array{ot_hours: float, ot_amount: float, ot_start_at: null, ot_end_at: null} */
    private function zeroOt(): array
    {
        return [
            'ot_hours'    => 0,
            'ot_amount'   => 0,
            'ot_start_at' => null,
            'ot_end_at'   => null,
        ];
    }
}
