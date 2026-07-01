<?php

namespace App\Console\Commands;

use App\Models\Tms\TmsDailyOdometerLog;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\TmsNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyTmsOdometerRemindersCommand extends Command
{
    protected $signature = 'tms:notify-odometer-reminders {--type=morning : morning or evening}';

    protected $description = 'Remind transport staff about missing morning or evening KM readings';

    public function handle(TmsNotificationService $notifications): int
    {
        $type = $this->option('type');

        if (! in_array($type, ['morning', 'evening'], true)) {
            $this->error('Type must be morning or evening.');

            return self::FAILURE;
        }

        $date = Carbon::today()->toDateString();
        $count = 0;

        if ($type === 'morning') {
            $vehicles = TmsVehicle::query()
                ->whereIn('status', ['available', 'on_trip'])
                ->orderBy('factory_id')
                ->get();

            foreach ($vehicles as $vehicle) {
                $hasMorning = TmsDailyOdometerLog::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->whereDate('log_date', $date)
                    ->whereNotNull('morning_km')
                    ->exists();

                if ($hasMorning) {
                    continue;
                }

                $notifications->odometerReminder($vehicle, 'morning', $date);
                $count++;
            }
        } else {
            $logs = TmsDailyOdometerLog::query()
                ->with('vehicle')
                ->whereDate('log_date', $date)
                ->whereNotNull('morning_km')
                ->whereNull('evening_km')
                ->get();

            foreach ($logs as $log) {
                if (! $log->vehicle) {
                    continue;
                }

                $notifications->odometerReminder($log->vehicle, 'evening', $date);
                $count++;
            }
        }

        $this->info("Sent {$count} {$type} odometer reminder(s) for {$date}.");

        return self::SUCCESS;
    }
}
