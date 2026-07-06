<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Shift;
use Carbon\Carbon;

class ShiftWorkCalculator
{
    /** @return array{start: Carbon, end: Carbon}|null */
    public function breakWindow(Carbon $date, ?Shift $shift): ?array
    {
        if (! $shift?->break_start_time || ! $shift?->break_end_time) {
            return null;
        }

        $start = Carbon::parse($date->toDateString() . ' ' . $shift->break_start_time);
        $end = Carbon::parse($date->toDateString() . ' ' . $shift->break_end_time);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->copy()->addDay();
        }

        return ['start' => $start, 'end' => $end];
    }

    public function shiftEndAt(Carbon $date, ?Shift $shift): ?Carbon
    {
        if (! $shift?->end_time) {
            return null;
        }

        $end = Carbon::parse($date->toDateString() . ' ' . $shift->end_time);

        if ($shift->is_night && $shift->start_time) {
            $start = Carbon::parse($date->toDateString() . ' ' . $shift->start_time);

            if ($end->lessThanOrEqualTo($start)) {
                $end = $end->copy()->addDay();
            }
        }

        return $end;
    }

    public function workMinutes(Carbon $checkIn, ?Carbon $checkOut, Carbon $date, ?Shift $shift): int
    {
        if (! $checkOut || ! $checkOut->greaterThan($checkIn)) {
            return 0;
        }

        $grossMinutes = $checkIn->diffInMinutes($checkOut);
        $breakWindow = $this->breakWindow($date, $shift);

        if ($breakWindow) {
            $overlap = $this->overlapMinutes(
                $checkIn,
                $checkOut,
                $breakWindow['start'],
                $breakWindow['end'],
            );

            return max(0, $grossMinutes - $overlap);
        }

        $breakMinutes = (int) ($shift?->break_minutes ?? 0);

        if ($breakMinutes <= 0) {
            return $grossMinutes;
        }

        $deductBreak = $grossMinutes >= $breakMinutes;

        return max(0, $grossMinutes - ($deductBreak ? $breakMinutes : 0));
    }

    public function resolvedBreakMinutes(Carbon $date, ?Shift $shift): int
    {
        $breakWindow = $this->breakWindow($date, $shift);

        if ($breakWindow) {
            return (int) $breakWindow['start']->diffInMinutes($breakWindow['end']);
        }

        return (int) ($shift?->break_minutes ?? 0);
    }

    /**
     * Lunch-based automatic half day detection.
     *
     * @return array{is_half_day: bool, type: string|null}
     */
    public function lunchHalfDay(Carbon $date, Carbon $checkIn, ?Carbon $checkOut, ?Shift $shift): array
    {
        $breakWindow = $this->breakWindow($date, $shift);

        if (! $breakWindow) {
            return ['is_half_day' => false, 'type' => null];
        }

        $lunchStart = $breakWindow['start'];
        $lunchEnd = $breakWindow['end'];

        if ($checkIn->greaterThanOrEqualTo($lunchStart)) {
            return ['is_half_day' => true, 'type' => 'second_half'];
        }

        if ($checkOut
            && $checkIn->lessThan($lunchStart)
            && $checkOut->greaterThanOrEqualTo($lunchStart)
            && $checkOut->lessThanOrEqualTo($lunchEnd)
        ) {
            return ['is_half_day' => true, 'type' => 'first_half'];
        }

        return ['is_half_day' => false, 'type' => null];
    }

    public function expectedShiftMinutes(?Shift $shift): int
    {
        if (! $shift || ! $shift->start_time || ! $shift->end_time) {
            return 480;
        }

        $start = Carbon::parse($shift->start_time);
        $end = Carbon::parse($shift->end_time);

        if ($shift->is_night && $end->lte($start)) {
            $end = $end->copy()->addDay();
        }

        $date = Carbon::today();
        $breakMinutes = $this->resolvedBreakMinutes($date, $shift);

        return max($start->diffInMinutes($end) - $breakMinutes, 1);
    }

    private function overlapMinutes(Carbon $rangeStart, Carbon $rangeEnd, Carbon $windowStart, Carbon $windowEnd): int
    {
        $overlapStart = $rangeStart->greaterThan($windowStart) ? $rangeStart : $windowStart;
        $overlapEnd = $rangeEnd->lessThan($windowEnd) ? $rangeEnd : $windowEnd;

        if ($overlapEnd->lessThanOrEqualTo($overlapStart)) {
            return 0;
        }

        return (int) $overlapStart->diffInMinutes($overlapEnd);
    }
}
