<?php

$m = require __DIR__ . '/../data/tms_maintenance.php';
$billMap = [];
$dupes = [];

foreach ($m as $reg => $vehicleData) {
    foreach ($vehicleData['bills'] as $bill) {
        $no = (string) ($bill['bill_no'] ?? '');
        if ($no === '') {
            continue;
        }
        if (isset($billMap[$no])) {
            $dupes[$no][] = $billMap[$no];
            $dupes[$no][] = $reg;
        } else {
            $billMap[$no] = $reg;
        }
    }
}

echo 'Duplicate bill_no across vehicles: ' . count($dupes) . PHP_EOL;
foreach ($dupes as $no => $regs) {
    echo $no . ' => ' . implode(', ', array_unique($regs)) . PHP_EOL;
}
