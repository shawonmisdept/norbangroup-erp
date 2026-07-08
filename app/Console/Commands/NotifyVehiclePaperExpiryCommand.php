<?php

namespace App\Console\Commands;

use App\Models\Tms\TmsVehicle;
use App\Services\Tms\TmsNotificationService;
use App\Services\Tms\VehiclePaperService;
use Illuminate\Console\Command;

class NotifyVehiclePaperExpiryCommand extends Command
{
    protected $signature = 'tms:notify-vehicle-paper-expiry';

    protected $description = 'Notify transport staff about expired or soon-expiring vehicle papers';

    public function handle(TmsNotificationService $notifications, VehiclePaperService $papers): int
    {
        $count = 0;

        $vehicles = TmsVehicle::query()->orderBy('factory_id')->get();

        foreach ($vehicles as $vehicle) {
            $status = $papers->worstStatusForVehicle($vehicle);

            if (! in_array($status, [VehiclePaperService::STATUS_EXPIRED, VehiclePaperService::STATUS_URGENT], true)) {
                continue;
            }

            $warnings = $papers->warningMessagesForVehicle($vehicle);
            if ($warnings === []) {
                continue;
            }

            $notifications->vehiclePaperAlert($vehicle, $status, $warnings);
            $count++;
        }

        $this->info("Sent {$count} vehicle paper alert(s).");

        return self::SUCCESS;
    }
}
