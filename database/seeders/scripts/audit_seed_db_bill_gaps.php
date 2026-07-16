<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsVehicle;

$seed = require __DIR__ . '/../data/tms_maintenance.php';
$vehicles = TmsVehicle::pluck('id', 'reg_number');

$missingInDb = [];
$duplicateInSeed = [];

foreach ($seed as $reg => $vehicleData) {
    $vehicleId = $vehicles[$reg] ?? null;
    if (! $vehicleId) {
        continue;
    }

    $dbBillNos = TmsMaintenanceBill::where('vehicle_id', $vehicleId)->pluck('bill_no')->all();
    $dbSet = array_fill_keys($dbBillNos, true);

    $seen = [];
    foreach ($vehicleData['bills'] as $bill) {
        $no = (string) ($bill['bill_no'] ?? '');
        if ($no === '') {
            continue;
        }

        if (isset($seen[$no])) {
            $duplicateInSeed[] = [
                'reg' => $reg,
                'bill_no' => $no,
                'total' => (float) ($bill['total_amount'] ?? 0),
            ];
        }
        $seen[$no] = true;

        if (($bill['items'] ?? []) === [] || empty($bill['bill_date'])) {
            $missingInDb[] = [
                'reg' => $reg,
                'bill_no' => $no,
                'reason' => 'empty items/date in seed',
                'total' => (float) ($bill['total_amount'] ?? 0),
            ];
            continue;
        }

        if (! isset($dbSet[$no])) {
            $missingInDb[] = [
                'reg' => $reg,
                'bill_no' => $no,
                'reason' => 'not in DB',
                'total' => (float) ($bill['total_amount'] ?? 0),
                'items' => count($bill['items'] ?? []),
            ];
        }
    }
}

echo 'Missing/skipped bills in DB: ' . count($missingInDb) . PHP_EOL;
$missingTotal = 0;
foreach ($missingInDb as $row) {
    $missingTotal += $row['total'];
    echo sprintf(
        "  %s | %s | %s | %.2f | %s\n",
        $row['reg'],
        $row['bill_no'],
        $row['reason'],
        $row['total'],
        isset($row['items']) ? "items={$row['items']}" : ''
    );
}
echo 'Missing total BDT: ' . number_format($missingTotal, 2) . PHP_EOL;

echo PHP_EOL . 'Duplicate bill_no within same vehicle (seed): ' . count($duplicateInSeed) . PHP_EOL;
foreach (array_slice($duplicateInSeed, 0, 30) as $row) {
    echo "  {$row['reg']} | {$row['bill_no']} | {$row['total']}" . PHP_EOL;
}
