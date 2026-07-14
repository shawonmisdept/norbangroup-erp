<?php

namespace App\Services\Tms;

use App\Models\Hrm\Employee;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTripLog;
use Carbon\Carbon;

class DriverPayCalculator
{
    public function __construct(
        private HolidayDetector $holidayDetector,
    ) {}

    /** @return array<string, mixed> */
    public function calculate(TmsTripLog $tripLog): array
    {
        $tripLog->loadMissing([
            'driver.employee.shift',
            'rentalDriver',
            'transportRequests',
            'transportRequest',
        ]);

        $request = $tripLog->primaryRequest();

        if (! $request || ! $tripLog->duty_end_at) {
            return $this->zeroPay();
        }

        $settings = TmsSetting::current();
        if (! $settings->exists) {
            $settings = new TmsSetting(TmsSetting::defaultValues());
        }
        if ($tripLog->rental_driver_id) {
            return $this->calculateRentalDriverPay($tripLog, $request, $settings);
        }

        if ($tripLog->driver_id) {
            return $this->calculateCompanyDriverPay($tripLog, $request, $settings);
        }

        return $this->zeroPay();
    }

    /** @return array<string, mixed> */
    private function calculateCompanyDriverPay(TmsTripLog $tripLog, $request, TmsSetting $settings): array
    {
        /** @var TmsDriver|null $driver */
        $driver = $tripLog->driver;

        if (! $driver || ! $driver->is_overtime_active) {
            return $this->zeroPay('company');
        }

        $dutyDate = $request->pickup_at->copy();
        $officeEnd = $this->otThreshold($tripLog->factory_id, $driver, $request->pickup_at);
        $officeStart = $this->holidayDetector->officeStart($tripLog->factory_id, $dutyDate);

        if (! $officeEnd || $tripLog->duty_end_at->lte($officeEnd)) {
            return $this->zeroPay('company');
        }

        $isHoliday = $this->holidayDetector->isHolidayOrWeekend($tripLog->factory_id, $dutyDate);

        if ($isHoliday) {
            return $this->holidayCompanyPay($tripLog, $officeEnd, $officeStart, $driver, $settings);
        }

        return $this->buildPay([
            'driver_type'         => 'company',
            'bill_type'           => 'night_bill',
            'night_bill_amount'   => (float) $settings->company_night_bill,
            'holiday_duty_amount' => 0,
            'ot_hours'            => 0,
            'ot_hourly_amount'    => 0,
            'ot_start_at'         => $officeEnd,
            'ot_end_at'           => $tripLog->duty_end_at,
        ]);
    }

    /** @return array<string, mixed> */
    private function holidayCompanyPay(
        TmsTripLog $tripLog,
        Carbon $officeEnd,
        Carbon $officeStart,
        TmsDriver $driver,
        TmsSetting $settings,
    ): array {
        $holidayBill = $tripLog->duty_end_at->gt($officeStart)
            ? (float) $settings->company_holiday_duty_bill
            : 0;

        $otHours = 0.0;
        $otHourly = 0.0;
        $otStart = null;

        if ($tripLog->duty_end_at->gt($officeEnd)) {
            $otStart = $officeEnd;
            $otHours = round($officeEnd->diffInMinutes($tripLog->duty_end_at) / 60, 2);
            $otHourly = round($otHours * (float) $driver->ot_rate, 2);
        }

        return $this->buildPay([
            'driver_type'         => 'company',
            'bill_type'           => $otHours > 0 ? 'holiday_mixed' : 'holiday_duty',
            'night_bill_amount'   => 0,
            'holiday_duty_amount' => $holidayBill,
            'ot_hours'            => $otHours,
            'ot_hourly_amount'    => $otHourly,
            'ot_start_at'         => $otStart,
            'ot_end_at'           => $otHours > 0 ? $tripLog->duty_end_at : null,
        ]);
    }

    /** @return array<string, mixed> */
    private function calculateRentalDriverPay(TmsTripLog $tripLog, $request, TmsSetting $settings): array
    {
        if (! $tripLog->rental_driver_id) {
            return $this->zeroPay('rental');
        }

        $officeEnd = $this->holidayDetector->officeEnd($tripLog->factory_id, $request->pickup_at);

        if ($tripLog->duty_end_at->lte($officeEnd)) {
            return $this->zeroPay('rental');
        }

        $otHours = round($officeEnd->diffInMinutes($tripLog->duty_end_at) / 60, 2);
        $rate = (float) $settings->rental_ot_hourly_rate;
        $otHourly = round($otHours * $rate, 2);

        return $this->buildPay([
            'driver_type'         => 'rental',
            'bill_type'           => 'hourly',
            'night_bill_amount'   => 0,
            'holiday_duty_amount' => 0,
            'ot_hours'            => $otHours,
            'ot_hourly_amount'    => $otHourly,
            'ot_start_at'         => $officeEnd,
            'ot_end_at'           => $tripLog->duty_end_at,
        ]);
    }

    public function otThreshold(int $factoryId, TmsDriver $driver, Carbon $pickupAt): ?Carbon
    {
        $settings = TmsSetting::current();
        $basis = $settings->ot_basis ?? 'global_office_time';

        if ($basis === 'employee_shift_end') {
            $employee = Employee::with('shift')->find($driver->employee_id);
            $endTime = $employee?->shift?->end_time;

            if ($endTime) {
                return $this->combineDateAndTime($pickupAt, $endTime);
            }
        }

        return $this->holidayDetector->officeEnd($factoryId, $pickupAt);
    }

    private function combineDateAndTime(Carbon $date, mixed $time): Carbon
    {
        $timeStr = $time instanceof Carbon ? $time->format('H:i:s') : (string) $time;

        return Carbon::parse($date->toDateString() . ' ' . $timeStr);
    }

    /** @param  array<string, mixed>  $parts */
    private function buildPay(array $parts): array
    {
        $total = round(
            (float) ($parts['night_bill_amount'] ?? 0)
            + (float) ($parts['holiday_duty_amount'] ?? 0)
            + (float) ($parts['ot_hourly_amount'] ?? 0),
            2
        );

        return [
            'driver_type'         => $parts['driver_type'] ?? null,
            'bill_type'           => $parts['bill_type'] ?? 'none',
            'night_bill_amount'   => (float) ($parts['night_bill_amount'] ?? 0),
            'holiday_duty_amount' => (float) ($parts['holiday_duty_amount'] ?? 0),
            'ot_hours'            => (float) ($parts['ot_hours'] ?? 0),
            'ot_hourly_amount'    => (float) ($parts['ot_hourly_amount'] ?? 0),
            'total_driver_pay'    => $total,
            'ot_amount'           => $total,
            'ot_start_at'         => $parts['ot_start_at'] ?? null,
            'ot_end_at'           => $parts['ot_end_at'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    private function zeroPay(?string $driverType = null): array
    {
        return [
            'driver_type'         => $driverType,
            'bill_type'           => 'none',
            'night_bill_amount'   => 0,
            'holiday_duty_amount' => 0,
            'ot_hours'            => 0,
            'ot_hourly_amount'    => 0,
            'total_driver_pay'    => 0,
            'ot_amount'           => 0,
            'ot_start_at'         => null,
            'ot_end_at'           => null,
        ];
    }
}
