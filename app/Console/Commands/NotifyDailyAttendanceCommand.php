<?php

namespace App\Console\Commands;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\Employee;
use App\Services\Hrm\HrmNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyDailyAttendanceCommand extends Command
{
    protected $signature = 'hrm:notify-daily-attendance {--date= : Y-m-d date to report (default yesterday)}';

    protected $description = 'Send late/absent alerts to HR and line chiefs';

    public function handle(HrmNotificationService $notifications): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->subDay()->startOfDay();

        $dateLabel = $date->format('d M Y');

        Factory::query()->where('is_active', true)->each(function (Factory $factory) use ($notifications, $date, $dateLabel) {
            $logs = AttendanceDailyLog::query()
                ->where('factory_id', $factory->id)
                ->whereDate('attendance_date', $date)
                ->whereIn('status', ['late', 'absent'])
                ->with('employee')
                ->get();

            $lateCount = $logs->where('status', 'late')->count();
            $absentCount = $logs->where('status', 'absent')->count();

            $notifications->dailyAttendanceSummary(
                $factory->id,
                $factory->name,
                $lateCount,
                $absentCount,
                $dateLabel
            );

            $logs->groupBy(fn ($log) => $log->employee?->reporting_to_id)->each(function ($group, $managerId) use ($notifications, $dateLabel) {
                if (! $managerId) {
                    return;
                }

                $manager = Employee::find($managerId);

                if (! $manager) {
                    return;
                }

                $notifications->lineChiefAttendanceAlert(
                    $manager,
                    $group->where('status', 'late')->count(),
                    $group->where('status', 'absent')->count(),
                    $dateLabel
                );
            });
        });

        $this->info('Daily attendance alerts processed for ' . $dateLabel);

        return self::SUCCESS;
    }
}
