<?php

$m = require __DIR__ . '/../data/tms_maintenance.php';

$targets = ['DM-GA-30-0062', 'DM-KHA-23-5772', 'DM-U-4801'];
$billOwners = [];

foreach ($m as $reg => $vehicleData) {
    foreach ($vehicleData['bills'] as $bill) {
        $no = (string) ($bill['bill_no'] ?? '');
        if ($no !== '') {
            $billOwners[$no][] = $reg;
        }
    }
}

foreach ($targets as $reg) {
    echo PHP_EOL . "=== {$reg} ===" . PHP_EOL;
    $bills = $m[$reg]['bills'] ?? [];
    $total = 0.0;
    $collisions = 0;

    foreach ($bills as $bill) {
        $no = (string) $bill['bill_no'];
        $owners = array_unique($billOwners[$no] ?? []);
        $collision = count($owners) > 1;
        if ($collision) {
            $collisions++;
        }

        $total += (float) ($bill['total_amount'] ?? 0);
        echo sprintf(
            "%s | %s | %s | BDT %s%s\n",
            $bill['bill_date'] ?? '',
            $no,
            $bill['workshop_name'] ?? '',
            number_format((float) ($bill['total_amount'] ?? 0), 2),
            $collision ? ' [COLLISION: ' . implode(', ', $owners) . ']' : ''
        );
    }

    echo 'Bills: ' . count($bills) . ' | Items: ' . array_sum(array_map(static fn ($b) => count($b['items'] ?? []), $bills));
    echo ' | Total: BDT ' . number_format($total, 2) . ' | Colliding bill_no: ' . $collisions . PHP_EOL;
}
