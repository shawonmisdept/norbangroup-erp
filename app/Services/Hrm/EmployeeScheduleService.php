<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeeScheduleService
{
    public const WEEKDAY_LABELS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public const HALF_DAY_TYPES = [
        'first_half'  => 'First Half',
        'second_half' => 'Second Half',
        'auto'        => 'Half Day (Auto)',
    ];

    /** @return list<int> */
    public function weekendDays(Employee $employee): array
    {
        $days = $employee->weekend_days;

        if (! is_array($days) || $days === []) {
            return [0];
        }

        return array_values(array_unique(array_map('intval', $days)));
    }

    public function isWeekend(Employee $employee, Carbon $date): bool
    {
        return in_array((int) $date->dayOfWeek, $this->weekendDays($employee), true);
    }

    public function allowsWeekendOt(Employee $employee): bool
    {
        return (bool) $employee->weekend_ot_allowed;
    }

    public function isHoliday(int $factoryId, Carbon $date): bool
    {
        return Holiday::query()
            ->where('factory_id', $factoryId)
            ->where('is_active', true)
            ->whereDate('date', $date->toDateString())
            ->exists();
    }

    public function isNonWorkingDay(Employee $employee, Carbon $date): bool
    {
        return $this->isWeekend($employee, $date) || $this->isHoliday($employee->factory_id, $date);
    }

    public function halfDayPayRatio(Employee $employee, ?AttendanceDailyLog $log, ?AttendancePolicy $policy = null): float
    {
        if ($log?->half_day_pay_ratio !== null) {
            return (float) $log->half_day_pay_ratio;
        }

        if ($employee->half_day_pay_ratio !== null) {
            return (float) $employee->half_day_pay_ratio;
        }

        $policy ??= AttendancePolicy::forFactory($employee->factory_id);

        return (float) ($policy->default_half_day_pay_ratio ?? 0.5);
    }

    public function resolveShift(Employee $employee, Carbon $date): ?Shift
    {
        return app(ShiftRosterService::class)->resolveShift($employee, $date);
    }

    /** @param Collection<int, AttendanceDailyLog> $logs */
    public function halfDaySummary(Employee $employee, Collection $logs, ?AttendancePolicy $policy = null): array
    {
        $policy ??= AttendancePolicy::forFactory($employee->factory_id);
        $first = 0;
        $second = 0;
        $auto = 0;
        $paidUnits = 0.0;

        foreach ($logs->where('status', 'half_day') as $log) {
            $ratio = $this->halfDayPayRatio($employee, $log, $policy);
            $type = $log->half_day_type ?? 'auto';

            match ($type) {
                'first_half'  => $first++,
                'second_half' => $second++,
                default       => $auto++,
            };

            $paidUnits += $ratio;
        }

        return [
            'first_half'  => $first,
            'second_half' => $second,
            'auto'        => $auto,
            'total'       => $first + $second + $auto,
            'paid_units'  => round($paidUnits, 2),
        ];
    }

    public function detectHalfDayType(Carbon $date, Carbon $checkIn, ?Carbon $checkOut, ?Shift $shift): string
    {
        if (! $shift?->start_time || ! $shift?->end_time) {
            return 'auto';
        }

        $expectedIn = Carbon::parse($date->toDateString() . ' ' . $shift->start_time);
        $expectedOut = Carbon::parse($date->toDateString() . ' ' . $shift->end_time);
        $midPoint = $expectedIn->copy()->addMinutes($expectedIn->diffInMinutes($expectedOut) / 2);

        if ($checkIn->greaterThanOrEqualTo($midPoint)) {
            return 'second_half';
        }

        if ($checkOut && $checkOut->lessThanOrEqualTo($midPoint)) {
            return 'first_half';
        }

        return 'auto';
    }

    public function weekendDaysLabel(Employee $employee): string
    {
        $labels = collect($this->weekendDays($employee))
            ->map(fn ($d) => self::WEEKDAY_LABELS[$d] ?? (string) $d)
            ->all();

        return $labels !== [] ? implode(', ', $labels) : '—';
    }
}
