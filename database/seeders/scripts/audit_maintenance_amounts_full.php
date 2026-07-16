<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsVehicle;

$seed = require __DIR__ . '/../data/tms_maintenance.php';
$tolerance = 0.01;

echo '=== FULL AMOUNT CROSS-CHECK ===' . PHP_EOL;

// 1. Seed internal consistency
$seedIssues = 0;
$seedBills = 0;
foreach ($seed as $reg => $vehicleData) {
    foreach ($vehicleData['bills'] as $bill) {
        $seedBills++;
        $sum = round(array_sum(array_column($bill['items'] ?? [], 'amount')), 2);
        $total = round((float) ($bill['total_amount'] ?? 0), 2);
        if (abs($total - $sum) > $tolerance) {
            $seedIssues++;
        }
    }
}
echo "Seed bills: {$seedBills}, amount mismatches: {$seedIssues}" . PHP_EOL;

// 2. DB internal consistency (all bills)
$dbIssues = [];
$dbBills = 0;
foreach (TmsMaintenanceBill::with(['vehicle:id,reg_number', 'items'])->cursor() as $bill) {
    $dbBills++;
    $sum = round((float) $bill->items->sum('amount'), 2);
    $total = round((float) $bill->total_amount, 2);
    if (abs($total - $sum) > $tolerance) {
        $dbIssues[] = [
            'reg' => $bill->vehicle?->reg_number,
            'bill_no' => $bill->bill_no,
            'sum' => $sum,
            'total' => $total,
            'diff' => round($total - $sum, 2),
        ];
    }
}
echo "DB bills: {$dbBills}, amount mismatches: " . count($dbIssues) . PHP_EOL;

// 3. Seed vs DB per vehicle
echo PHP_EOL . '=== SEED vs DB COUNT/TOTAL ===' . PHP_EOL;
$countGaps = [];
$totalGaps = [];

$vehicles = TmsVehicle::pluck('id', 'reg_number');

foreach ($seed as $reg => $vehicleData) {
    $vehicleId = $vehicles[$reg] ?? null;
    if (! $vehicleId) {
        $countGaps[] = "{$reg}: vehicle missing in DB";
        continue;
    }

    $seedBillCount = count($vehicleData['bills']);
    $seedTotal = round(array_sum(array_map(
        static fn (array $b) => (float) ($b['total_amount'] ?? 0),
        $vehicleData['bills']
    )), 2);

    $dbQuery = TmsMaintenanceBill::where('vehicle_id', $vehicleId);
    $dbBillCount = (clone $dbQuery)->count();
    $dbTotal = round((float) (clone $dbQuery)->sum('total_amount'), 2);

    if ($seedBillCount !== $dbBillCount) {
        $countGaps[] = "{$reg}: seed={$seedBillCount} db={$dbBillCount}";
    }

    if (abs($seedTotal - $dbTotal) > $tolerance) {
        $totalGaps[] = sprintf('%s: seed=%.2f db=%.2f diff=%+.2f', $reg, $seedTotal, $dbTotal, $dbTotal - $seedTotal);
    }
}

echo 'Count gaps: ' . count($countGaps) . PHP_EOL;
foreach (array_slice($countGaps, 0, 20) as $line) {
    echo "  {$line}" . PHP_EOL;
}

echo 'Total amount gaps: ' . count($totalGaps) . PHP_EOL;
foreach (array_slice($totalGaps, 0, 20) as $line) {
    echo "  {$line}" . PHP_EOL;
}

// 4. User-provided vehicles month breakdown
echo PHP_EOL . '=== MONTH SUBTOTALS (3 vehicles) ===' . PHP_EOL;
foreach (['DM-GA-30-0062', 'DM-KHA-23-5772', 'DM-U-4801'] as $reg) {
    echo PHP_EOL . $reg . ':' . PHP_EOL;
    $vehicleId = $vehicles[$reg] ?? null;
    if (! $vehicleId) {
        echo "  missing" . PHP_EOL;
        continue;
    }

    $bills = TmsMaintenanceBill::where('vehicle_id', $vehicleId)->with('items')->get();
    $byMonth = $bills->groupBy(fn ($b) => $b->bill_date?->format('Y-m'));

    foreach ($byMonth->sortKeysDesc() as $month => $monthBills) {
        $billTotalSum = round($monthBills->sum('total_amount'), 2);
        $itemTotalSum = round($monthBills->sum(fn ($b) => $b->items->sum('amount')), 2);
        echo sprintf(
            "  %s | bills=%d | bill_total=%.2f | item_sum=%.2f%s\n",
            $month,
            $monthBills->count(),
            $billTotalSum,
            $itemTotalSum,
            abs($billTotalSum - $itemTotalSum) > $tolerance ? ' MISMATCH' : ''
        );
    }
}

if ($dbIssues !== []) {
    echo PHP_EOL . 'DB amount mismatches:' . PHP_EOL;
    foreach (array_slice($dbIssues, 0, 20) as $row) {
        echo sprintf(
            "  %s | %s | items=%.2f total=%.2f diff=%+.2f\n",
            $row['reg'],
            $row['bill_no'],
            $row['sum'],
            $row['total'],
            $row['diff']
        );
    }
}
