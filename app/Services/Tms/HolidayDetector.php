<?php

namespace App\Services\Tms;

use App\Models\Hrm\Holiday;
use App\Models\Tms\TmsSetting;
use Carbon\Carbon;

class HolidayDetector
{
    public function isHolidayOrWeekend(int $factoryId, Carbon $date): bool
    {
        $settings = TmsSetting::where('factory_id', $factoryId)->first();
        $weekendDays = $settings?->weekend_days ?? TmsSetting::defaultValues()['weekend_days'];

        if (in_array($date->dayOfWeek, $weekendDays, true)) {
            return true;
        }

        return Holiday::query()
            ->where('factory_id', $factoryId)
            ->where('is_active', true)
            ->whereDate('date', $date->toDateString())
            ->exists();
    }

    public function officeStart(int $factoryId, Carbon $date): Carbon
    {
        $settings = TmsSetting::where('factory_id', $factoryId)->first();
        $start = $settings?->office_start ?? TmsSetting::defaultValues()['office_start'];

        return $this->combineDateAndTime($date, $start);
    }

    public function officeEnd(int $factoryId, Carbon $date): Carbon
    {
        $settings = TmsSetting::where('factory_id', $factoryId)->first();
        $end = $settings?->office_end ?? TmsSetting::defaultValues()['office_end'];

        return $this->combineDateAndTime($date, $end);
    }

    private function combineDateAndTime(Carbon $date, mixed $time): Carbon
    {
        $timeStr = $time instanceof Carbon ? $time->format('H:i:s') : (string) $time;

        return Carbon::parse($date->toDateString() . ' ' . $timeStr);
    }
}
