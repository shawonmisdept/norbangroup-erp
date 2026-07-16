<?php

$m = require __DIR__ . '/../data/tms_maintenance.php';
$bills = $m['DM-U-4801']['bills'] ?? [];
echo 'Seed file bills: ' . count($bills) . PHP_EOL;
foreach ($bills as $i => $bill) {
    echo $i . ': ' . ($bill['bill_no'] ?? '?') . ' | ' . ($bill['bill_date'] ?? '') . ' | items=' . count($bill['items'] ?? []) . PHP_EOL;
}
