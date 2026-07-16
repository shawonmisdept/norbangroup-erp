<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsVehicle;

echo 'Vehicles: ' . TmsVehicle::count() . PHP_EOL;
echo 'Maintenance bills: ' . TmsMaintenanceBill::count() . PHP_EOL;

foreach (['DM-GA-30-0062', 'DM-KHA-23-5772', 'DM-U-4801'] as $reg) {
    $vehicle = TmsVehicle::where('reg_number', $reg)->first();
    $billCount = $vehicle
        ? TmsMaintenanceBill::where('vehicle_id', $vehicle->id)->count()
        : 0;

    echo "{$reg}: vehicle=" . ($vehicle ? 'yes' : 'NO') . ", bills={$billCount}" . PHP_EOL;
}
