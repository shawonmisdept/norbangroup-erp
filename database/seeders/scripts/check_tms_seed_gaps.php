<?php

$vehicles = require __DIR__ . '/../data/tms_vehicles.php';
$maint = require __DIR__ . '/../data/tms_maintenance.php';

$normalize = static function (string $regNumber): string {
    $parts = preg_split('/[\s\-]+/', trim($regNumber)) ?: [];

    return implode('-', array_map(
        static fn (string $part) => strtoupper($part),
        array_values(array_filter($parts, static fn (string $part) => $part !== ''))
    ));
};

$vRegs = array_map(fn ($row) => $normalize($row['reg_number'] ?? ''), $vehicles);
$mKeys = array_map($normalize, array_keys($maint));

$orphanMaint = array_values(array_diff($mKeys, $vRegs));
$missingMaint = array_values(array_diff($vRegs, $mKeys));

$suffix = static function (string $regNumber) use ($normalize): string {
    $parts = explode('-', $normalize($regNumber));

    return end($parts) ?: '';
};

$suffixToVehicle = [];
foreach ($vRegs as $reg) {
    $suffixToVehicle[$suffix($reg)] = $reg;
}

echo 'Vehicles: ' . count($vRegs) . PHP_EOL;
echo 'Maintenance keys: ' . count($mKeys) . PHP_EOL;
echo 'Orphan maintenance (no vehicle): ' . count($orphanMaint) . PHP_EOL;
foreach ($orphanMaint as $r) {
    $hint = $suffixToVehicle[$suffix($r)] ?? null;
    echo '  - ' . $r . ($hint ? " (suffix match → {$hint})" : '') . PHP_EOL;
}
echo 'Vehicles missing maintenance: ' . count($missingMaint) . PHP_EOL;
foreach ($missingMaint as $r) {
    echo '  - ' . $r . PHP_EOL;
}
