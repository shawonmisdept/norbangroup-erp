<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsVehicle;

$vehicleId = TmsVehicle::where('reg_number', 'DM-GHA-11-8402')->value('id');

foreach (TmsMaintenanceBill::where('vehicle_id', $vehicleId)->where('bill_date', 'like', '2024-10%')->get(['bill_no', 'bill_date', 'month_of']) as $bill) {
    echo "{$bill->bill_no} | {$bill->bill_date->toDateString()} | month_of=" . ($bill->month_of ?? 'NULL') . PHP_EOL;
}

echo 'Total missing month_of: ' . TmsMaintenanceBill::where('vehicle_id', $vehicleId)->whereNull('month_of')->count() . PHP_EOL;
