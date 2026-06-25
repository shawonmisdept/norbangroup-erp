<?php

namespace App\Console\Commands;

use App\Models\Hrm\Employee;
use App\Services\Hrm\HrmNotificationService;
use Illuminate\Console\Command;

class NotifyEmploymentMilestonesCommand extends Command
{
    protected $signature = 'hrm:notify-employment-milestones';

    protected $description = 'Alert HR about contract expiry and probation end dates';

    public function handle(HrmNotificationService $notifications): int
    {
        $today = now()->startOfDay();

        Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->whereNotNull('contract_end_date')
            ->each(function (Employee $employee) use ($notifications, $today) {
                $days = $today->diffInDays($employee->contract_end_date, false);

                if (in_array($days, [90, 30, 7], true)) {
                    $notifications->contractExpiry($employee, $days);
                }
            });

        Employee::query()
            ->where('status', 'probation')
            ->where(function ($query) {
                $query->whereNotNull('probation_end_date')
                    ->orWhereNotNull('confirmation_date');
            })
            ->each(function (Employee $employee) use ($notifications, $today) {
                $endDate = $employee->probation_end_date ?? $employee->confirmation_date;

                if (! $endDate) {
                    return;
                }

                $days = $today->diffInDays($endDate, false);

                if (in_array($days, [30, 7], true)) {
                    $notifications->probationEnd($employee, $days);
                }
            });

        $this->info('Employment milestone alerts processed.');

        return self::SUCCESS;
    }
}
