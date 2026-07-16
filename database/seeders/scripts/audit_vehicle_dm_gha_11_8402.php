<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsVehicle;

$reg = 'DM-GHA-11-8402';
$seed = require __DIR__ . '/../data/tms_maintenance.php';
$vehicle = TmsVehicle::where('reg_number', $reg)->first();

echo "=== {$reg} (Hundai Zeep) ===" . PHP_EOL;
echo 'Vehicle in DB: ' . ($vehicle ? 'yes (id=' . $vehicle->id . ')' : 'NO') . PHP_EOL;

$seedBills = $seed[$reg]['bills'] ?? [];
$seedTotal = round(array_sum(array_map(static fn ($b) => (float) ($b['total_amount'] ?? 0), $seedBills)), 2);
echo 'Seed bills: ' . count($seedBills) . ' | Total: BDT ' . number_format($seedTotal, 2) . PHP_EOL;

if ($vehicle) {
    $dbBills = TmsMaintenanceBill::where('vehicle_id', $vehicle->id)->with('items')->orderBy('bill_date')->get();
    $dbTotal = round((float) $dbBills->sum('total_amount'), 2);
    echo 'DB bills: ' . $dbBills->count() . ' | Total: BDT ' . number_format($dbTotal, 2) . PHP_EOL;
}

echo PHP_EOL . '=== BY MONTH ===' . PHP_EOL;
$byMonth = [];
foreach ($seedBills as $bill) {
    $month = substr((string) ($bill['bill_date'] ?? ''), 0, 7);
    $byMonth[$month]['bills'] = ($byMonth[$month]['bills'] ?? 0) + 1;
    $byMonth[$month]['total'] = ($byMonth[$month]['total'] ?? 0) + (float) ($bill['total_amount'] ?? 0);
}
krsort($byMonth);
foreach ($byMonth as $month => $row) {
    echo sprintf("  %s | bills=%d | BDT %s\n", $month, $row['bills'], number_format($row['total'], 2));
}

echo PHP_EOL . '=== BILL DETAILS (seed) ===' . PHP_EOL;
foreach (array_reverse($seedBills) as $bill) {
    $itemsSum = round(array_sum(array_column($bill['items'] ?? [], 'amount')), 2);
    $total = round((float) ($bill['total_amount'] ?? 0), 2);
    $flag = abs($total - $itemsSum) > 0.01 ? ' MISMATCH' : '';
    echo sprintf(
        "%s | %s | %s | items=%d | sum=%.2f | total=%.2f%s\n",
        $bill['bill_date'] ?? '',
        $bill['bill_no'] ?? '',
        $bill['workshop_name'] ?? '',
        count($bill['items'] ?? []),
        $itemsSum,
        $total,
        $flag
    );
}

// Check duplicates and suffix bills
echo PHP_EOL . '=== SUFFIX / MERGED BILLS ===' . PHP_EOL;
foreach ($seedBills as $bill) {
    if (preg_match('/\(\d+\)$/', (string) ($bill['bill_no'] ?? ''))) {
        echo '  ' . ($bill['bill_no'] ?? '') . ' | ' . ($bill['bill_date'] ?? '') . ' | ' . number_format((float) ($bill['total_amount'] ?? 0), 2) . PHP_EOL;
    }
}
