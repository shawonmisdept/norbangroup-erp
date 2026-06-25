<?php

namespace App\Console\Commands;

use App\Models\Factory;
use App\Models\Hrm\AttendancePeriod;
use App\Jobs\Hrm\ProcessAttendanceJob;
use App\Services\Hrm\AttendanceProcessor;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessAttendanceCommand extends Command
{
    protected $signature = 'hrm:process-attendance
                            {--date= : Process a single date (Y-m-d)}
                            {--factory= : Factory ID}
                            {--mark-absences : Mark absent for employees without punches}
                            {--queue : Dispatch per-factory jobs to the queue instead of running inline}';

    protected $description = 'Process raw biometric punches into daily attendance logs';

    public function handle(AttendanceProcessor $processor): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        $factoryQuery = Factory::query()->where('is_active', true);

        if ($this->option('factory')) {
            $factoryQuery->where('id', $this->option('factory'));
        }

        $factories = $factoryQuery->get();

        if ($factories->isEmpty()) {
            $this->error('No active factories found.');

            return self::FAILURE;
        }

        $total = 0;

        foreach ($factories as $factory) {
            if ($this->option('queue')) {
                ProcessAttendanceJob::dispatch(
                    $factory->id,
                    $date->toDateString(),
                    (bool) $this->option('mark-absences')
                );
                $this->line("{$factory->name}: queued for {$date->toDateString()}.");
                continue;
            }

            $period = AttendancePeriod::getOrCreateForMonth($factory->id, $date->year, $date->month);
            $processed = $processor->processDate($factory->id, $date, $period);
            $absences = 0;

            if ($this->option('mark-absences')) {
                $absences = $processor->markAbsences($factory->id, $date, $date, $period);
            }

            $total += $processed;
            $this->line("{$factory->name}: {$processed} punch day(s), {$absences} absent marked.");
        }

        $this->info("Done. Processed {$total} employee-day(s) for {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
