<?php

/**
 * Cross-check every maintenance bill: sum(line items) vs total_amount.
 * Usage:
 *   php database/seeders/scripts/audit_maintenance_amounts.php seed
 *   php database/seeders/scripts/audit_maintenance_amounts.php db
 */

$source = $argv[1] ?? 'both';
$tolerance = 0.01;

$mismatches = [];

if ($source === 'seed' || $source === 'both') {
    $data = require __DIR__ . '/../data/tms_maintenance.php';
    $checked = 0;

    foreach ($data as $reg => $vehicleData) {
        foreach ($vehicleData['bills'] as $bill) {
            $checked++;
            $itemsSum = round(array_sum(array_map(
                static fn (array $item) => (float) ($item['amount'] ?? 0),
                $bill['items'] ?? []
            )), 2);
            $total = round((float) ($bill['total_amount'] ?? 0), 2);
            $diff = round($total - $itemsSum, 2);

            if (abs($diff) > $tolerance) {
                $mismatches[] = [
                    'source' => 'seed',
                    'reg' => $reg,
                    'bill_no' => $bill['bill_no'] ?? '',
                    'bill_date' => $bill['bill_date'] ?? '',
                    'items_sum' => $itemsSum,
                    'total_amount' => $total,
                    'diff' => $diff,
                    'item_count' => count($bill['items'] ?? []),
                ];
            }
        }
    }

    echo '=== SEED FILE ===' . PHP_EOL;
    echo 'Bills checked: ' . $checked . PHP_EOL;
    echo 'Mismatches: ' . count(array_filter($mismatches, static fn ($m) => $m['source'] === 'seed')) . PHP_EOL;
}

if ($source === 'db' || $source === 'both') {
    require __DIR__ . '/../../../vendor/autoload.php';
    $app = require __DIR__ . '/../../../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $checked = 0;
    $dbMismatches = [];

    \App\Models\Tms\TmsMaintenanceBill::query()
        ->with(['vehicle:id,reg_number', 'items'])
        ->orderBy('vehicle_id')
        ->orderBy('bill_date')
        ->chunkById(200, function ($bills) use (&$checked, &$dbMismatches, $tolerance) {
            foreach ($bills as $bill) {
                $checked++;
                $itemsSum = round((float) $bill->items->sum('amount'), 2);
                $total = round((float) $bill->total_amount, 2);
                $diff = round($total - $itemsSum, 2);

                if (abs($diff) > $tolerance) {
                    $dbMismatches[] = [
                        'source' => 'db',
                        'reg' => $bill->vehicle?->reg_number ?? '?',
                        'bill_no' => $bill->bill_no,
                        'bill_date' => $bill->bill_date?->toDateString(),
                        'items_sum' => $itemsSum,
                        'total_amount' => $total,
                        'diff' => $diff,
                        'item_count' => $bill->items->count(),
                    ];
                }
            }
        });

    echo PHP_EOL . '=== DATABASE ===' . PHP_EOL;
    echo 'Bills checked: ' . $checked . PHP_EOL;
    echo 'Mismatches: ' . count($dbMismatches) . PHP_EOL;

    $mismatches = array_merge($mismatches, $dbMismatches);
}

if ($mismatches === []) {
    echo PHP_EOL . 'All bills match (items sum = total_amount).' . PHP_EOL;
    exit(0);
}

echo PHP_EOL . '=== MISMATCH DETAILS (first 50) ===' . PHP_EOL;

usort($mismatches, static fn ($a, $b) => abs($b['diff']) <=> abs($a['diff']));

foreach (array_slice($mismatches, 0, 50) as $row) {
    echo sprintf(
        "[%s] %s | %s | %s | items=%.2f total=%.2f diff=%+.2f (%d items)\n",
        $row['source'],
        $row['reg'],
        $row['bill_no'],
        $row['bill_date'],
        $row['items_sum'],
        $row['total_amount'],
        $row['diff'],
        $row['item_count']
    );
}

$byReg = [];
foreach ($mismatches as $row) {
    $byReg[$row['reg']] = ($byReg[$row['reg']] ?? 0) + 1;
}
arsort($byReg);

echo PHP_EOL . '=== TOP VEHICLES BY MISMATCH COUNT ===' . PHP_EOL;
foreach (array_slice($byReg, 0, 15, true) as $reg => $count) {
    echo "{$reg}: {$count}\n";
}

exit(1);
