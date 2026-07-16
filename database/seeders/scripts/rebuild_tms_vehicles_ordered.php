<?php

/**
 * Rebuild tms_vehicles.php in spreadsheet order with no duplicate array keys.
 */

$expectedOrder = [
    'DM-GHA-22-1042',
    'DM-GA-42-0117',
    'DM-GHA-13-8951',
    'DM-CHA-52-4571',
    'DM-BHA-11-0813',
    'DM-GHA-21-9271',
    'DM-GA-33-1788',
    'DM-GHA-02-0005',
    'DM-GHA-21-9272',
    'DM-GHA-16-2903',
    'DM-GHA-13-1531',
    'DM-GHA-21-7771',
    'DM-THA-11-7867',
    'DM-GHA-21-7770',
    'DM-GA-35-4897',
    'DM-GHA-11-8402',
    'DM-GA-45-2366',
    'DM-GA-35-7990',
    'DM-GA-15-7196',
    'DM-KHA-12-6032',
    'DM-GA-31-4810',
    'DM-GA-30-0062',
    'DM-KHA-23-5772',
    'DM-GA-43-9461',
    'DM-GA-13-5028',
    'DM-KHA-12-1223',
    'DM-KHA-13-1898',
    'DM-GA-13-9120',
    'DM-GA-19-9823',
    'DM-GA-23-3941',
    'DM-GA-37-9232',
    'DM-GA-37-9227',
    'DM-CHA-53-4286',
    'DM-CHA-11-3870',
    'DM-CHA-53-4349',
    'DM-CHA-53-4348',
    'DM-CHA-56-0973',
    'DM-CHA-56-1146',
    'DM-MA-11-6078',
    'DM-MA-11-6079',
    'DM-AU-11-2904',
    'NAR-MA-11-0043',
    'DM-AU-11-4206',
    'DM-AU-14-1095',
    'DM-U-4801',
    'DM-MA-51-8450',
    'DM-MA-14-0155',
    'DM-TA-15-7042',
    'DM-TA-13-6693',
    'DM-DA-11-9103',
    'DM-GHA-21-5864',
];

$fallbackByReg = [
    'DM-KHA-12-1223' => [
        'reg_number' => 'DM-KHA-12-1223',
        'name' => 'Toyota Corolla X',
        'vehicle_category' => 'sedan',
        'unit' => 'NFL',
        'model_year' => 2002,
        'purchase_date' => '22/Sep/05',
        'registration_date' => '6/Oct/05',
        'engine_cc' => 1300,
        'fuel_type' => 'CNG',
        'purchase_value' => '1,105,000',
        'fitness_expires_at' => '22-Jan-27',
        'tax_token_expires_at' => '6-Oct-26',
        'insurance_expires_at' => '9-Nov-26',
        'type' => 'own',
        'passenger_capacity' => 5,
        'status' => 'available',
        'registration_paper_status' => 'ok',
    ],
];

$current = require __DIR__ . '/../data/tms_vehicles.php';
$byReg = [];
foreach ($current as $row) {
    $byReg[strtoupper($row['reg_number'])] = $row;
}

$vehicles = [];
$missing = [];
foreach ($expectedOrder as $reg) {
    if (isset($byReg[$reg])) {
        $vehicles[] = $byReg[$reg];
        continue;
    }

    if (isset($fallbackByReg[$reg])) {
        $vehicles[] = $fallbackByReg[$reg];
        continue;
    }

    $missing[] = $reg;
}

if ($missing !== []) {
    fwrite(STDERR, 'Missing vehicle rows: ' . implode(', ', $missing) . PHP_EOL);
    exit(1);
}

$header = <<<'PHP'
<?php

/**
 * Vehicle register from "Vehicle Papers Status" spreadsheet (51 rows).
 * Blank / missing cells in the sheet are omitted — not filled with defaults.
 * Unit, allocated user, driver, and driver contact are set later in admin.
 *
 * Rebuild ordered list:
 *   php database/seeders/scripts/rebuild_tms_vehicles_ordered.php
 */

return 

PHP;

$outPath = __DIR__ . '/../data/tms_vehicles.php';
file_put_contents($outPath, $header . var_export($vehicles, true) . ";\n");

echo 'Wrote ' . count($vehicles) . ' vehicles' . PHP_EOL;
