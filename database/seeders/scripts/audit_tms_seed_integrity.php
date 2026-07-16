<?php

$vehicles = require __DIR__ . '/../data/tms_vehicles.php';
$maintenance = require __DIR__ . '/../data/tms_maintenance.php';

$normalize = static function (string $regNumber): string {
    $parts = preg_split('/[\s\-]+/', trim($regNumber)) ?: [];

    return implode('-', array_map(
        static fn (string $part) => strtoupper($part),
        array_values(array_filter($parts, static fn (string $part) => $part !== ''))
    ));
};

$suffix = static function (string $regNumber) use ($normalize): string {
    $parts = explode('-', $normalize($regNumber));

    return end($parts) ?: '';
};

$vehicleRegs = [];
$suffixToVehicle = [];
foreach ($vehicles as $row) {
    $reg = $normalize($row['reg_number'] ?? '');
    $vehicleRegs[] = $reg;
    $suffixToVehicle[$suffix($reg)] = $reg;
}

$maintenanceRegs = array_map($normalize, array_keys($maintenance));

echo '=== TMS Seed Integrity Audit ===' . PHP_EOL;
echo 'Vehicles: ' . count($vehicleRegs) . PHP_EOL;
echo 'Maintenance vehicles: ' . count($maintenanceRegs) . PHP_EOL . PHP_EOL;

$orphanMaint = array_values(array_diff($maintenanceRegs, $vehicleRegs));
$missingMaint = array_values(array_diff($vehicleRegs, $maintenanceRegs));

echo 'Orphan maintenance (no vehicle): ' . count($orphanMaint) . PHP_EOL;
foreach ($orphanMaint as $reg) {
    $hint = $suffixToVehicle[$suffix($reg)] ?? null;
    echo '  - ' . $reg . ($hint ? " (suffix match → {$hint})" : '') . PHP_EOL;
}

echo PHP_EOL . 'Vehicles missing maintenance: ' . count($missingMaint) . PHP_EOL;
foreach ($missingMaint as $reg) {
    echo '  - ' . $reg . PHP_EOL;
}

echo PHP_EOL . '=== Maintenance summary (user-provided vehicles) ===' . PHP_EOL;
foreach (['DM-GA-30-0062', 'DM-KHA-23-5772', 'DM-U-4801'] as $reg) {
    $bills = $maintenance[$reg]['bills'] ?? [];
    $itemCount = 0;
    $total = 0.0;
    foreach ($bills as $bill) {
        $itemCount += count($bill['items'] ?? []);
        $total += (float) ($bill['total_amount'] ?? 0);
    }

    echo $reg . ': ' . count($bills) . ' bills, ' . $itemCount . ' items, BDT ' . number_format($total, 2) . PHP_EOL;
}
