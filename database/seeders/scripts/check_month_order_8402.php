<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\MaintenanceService;

$vehicle = TmsVehicle::where('reg_number', 'DM-GHA-11-8402')->first();
$bills = TmsMaintenanceBill::where('vehicle_id', $vehicle->id)->with('items')->get();
$groups = app(MaintenanceService::class)->billsGroupedByMonth($bills);

echo "Month order (newest first):\n";
foreach ($groups as $key => $group) {
    $latest = $group->first()?->bill_date?->toDateString();
    echo "  {$key} | latest bill: {$latest} | bills: {$group->count()}\n";
}
