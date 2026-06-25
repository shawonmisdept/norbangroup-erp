<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkingHoursComplianceService
{
    public function __construct(
        private HrmNotificationService $notifications,
    ) {}

    /** @return list<array{employee: Employee, date: string, hours: float, limit: float}> */
    public function dailyViolations(int $factoryId, Carbon $from, Carbon $to): array
    {
        $policy = AttendancePolicy::forFactory($factoryId);
        $maxDaily = (float) ($policy->max_daily_hours ?? 10);

        if ($maxDaily <= 0) {
            return [];
        }

        $violations = [];

        $logs = AttendanceDailyLog::query()
            ->where('factory_id', $factoryId)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->where('work_minutes', '>', 0)
            ->with('employee:id,employee_code,name,factory_id')
            ->get();

        foreach ($logs as $log) {
            $hours = round((int) $log->work_minutes / 60, 2);

            if ($hours > $maxDaily) {
                $violations[] = [
                    'employee' => $log->employee,
                    'date'     => $log->attendance_date->format('Y-m-d'),
                    'hours'    => $hours,
                    'limit'    => $maxDaily,
                ];
            }
        }

        return $violations;
    }

    /** @return list<array{employee: Employee, week_start: string, hours: float, limit: float}> */
    public function weeklyViolations(int $factoryId, Carbon $from, Carbon $to): array
    {
        $policy = AttendancePolicy::forFactory($factoryId);
        $maxWeekly = (float) ($policy->max_weekly_hours ?? 60);

        if ($maxWeekly <= 0) {
            return [];
        }

        $logs = AttendanceDailyLog::query()
            ->where('factory_id', $factoryId)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->where('work_minutes', '>', 0)
            ->with('employee:id,employee_code,name,factory_id')
            ->get();

        $violations = [];

        $logs->groupBy('employee_id')->each(function (Collection $employeeLogs) use ($maxWeekly, &$violations) {
            $byWeek = $employeeLogs->groupBy(fn ($log) => $log->attendance_date->copy()->startOfWeek()->toDateString());

            foreach ($byWeek as $weekStart => $weekLogs) {
                $totalHours = round($weekLogs->sum('work_minutes') / 60, 2);

                if ($totalHours > $maxWeekly) {
                    $violations[] = [
                        'employee'   => $weekLogs->first()->employee,
                        'week_start' => $weekStart,
                        'hours'      => $totalHours,
                        'limit'      => $maxWeekly,
                    ];
                }
            }
        });

        return $violations;
    }

    public function notifyViolations(int $factoryId, Carbon $from, Carbon $to): int
    {
        $daily = $this->dailyViolations($factoryId, $from, $to);
        $weekly = $this->weeklyViolations($factoryId, $from, $to);
        $count = 0;

        foreach ($daily as $violation) {
            if ($violation['employee']) {
                $this->notifications->workingHoursExceeded(
                    $violation['employee'],
                    $violation['hours'],
                    $violation['limit'],
                    $violation['date'],
                    'daily'
                );
                $count++;
            }
        }

        foreach ($weekly as $violation) {
            if ($violation['employee']) {
                $this->notifications->workingHoursExceeded(
                    $violation['employee'],
                    $violation['hours'],
                    $violation['limit'],
                    $violation['week_start'],
                    'weekly'
                );
                $count++;
            }
        }

        return $count;
    }
}
