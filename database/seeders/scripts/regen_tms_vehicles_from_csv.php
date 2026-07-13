<?php

/**
 * Merge Sheet1 + Sheet2 CSVs into database/seeders/data/tms_vehicles.php
 * with vehicle_category + passenger_capacity inferred from model name.
 *
 * Prerequisite: CSVs at storage/app/temp-vehicle-xls/Sheet1.csv and Sheet2.csv
 *
 *   php database/seeders/scripts/regen_tms_vehicles_from_csv.php
 */

$normalize = static function (string $reg): string {
    $parts = preg_split('/[\s\-]+/', trim($reg)) ?: [];

    return implode('-', array_map(
        static fn (string $part) => strtoupper($part),
        array_values(array_filter($parts, static fn (string $part) => $part !== ''))
    ));
};

$parseCsv = static function (string $path): array {
    $fh = fopen($path, 'r');
    $rows = [];
    while (($data = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
        $rows[] = $data;
    }
    fclose($fh);

    return $rows;
};

$clean = static function (?string $value): ?string {
    if ($value === null) {
        return null;
    }
    $value = trim($value);
    if ($value === '' || strcasecmp($value, 'N/A') === 0 || $value === '-') {
        return null;
    }

    return $value;
};

$cleanMoney = static function (?string $value) use ($clean): ?string {
    $value = $clean($value);
    if ($value === null) {
        return null;
    }

    return trim(str_replace(['"', ' '], '', $value));
};

$mapCategoryAndSeats = static function (string $name): array {
    $n = strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');

    if (str_contains($n, 'cover')) {
        return ['other', 3];
    }

    if (str_contains($n, 'pic up') || str_contains($n, 'pickup') || str_contains($n, 'double cabin')) {
        return ['pickup', 5];
    }

    if (
        str_contains($n, 'micro')
        || str_contains($n, 'hiace')
        || str_contains($n, 'hayce')
        || str_contains($n, 'noah')
        || str_contains($n, 'urban')
        || str_contains($n, 'alphard')
    ) {
        if (str_contains($n, 'noah')) {
            return ['microbus', 8];
        }
        if (str_contains($n, 'alphard')) {
            return ['microbus', 7];
        }
        if (str_contains($n, 'urban')) {
            return ['microbus', 10];
        }

        return ['microbus', 12];
    }

    if (
        str_contains($n, 'jeep')
        || str_contains($n, 'zeep')
        || str_contains($n, 'x-trail')
        || str_contains($n, 'xtrail')
        || str_contains($n, 'haval')
        || str_contains($n, 'parado')
        || str_contains($n, 'prado')
        || str_contains($n, 'pajero')
        || str_contains($n, 'harrier')
    ) {
        if (str_contains($n, 'parado') || str_contains($n, 'prado') || str_contains($n, 'pajero')) {
            return ['jeep', 7];
        }

        return ['jeep', 5];
    }

    if (str_contains($n, 'wagun') || str_contains($n, 'wagon')) {
        return ['sedan', 5];
    }

    return ['sedan', 5];
};

$extractSheet1 = static function (array $rows, callable $normalize, callable $clean, callable $cleanMoney): array {
    $out = [];
    for ($i = 4; $i < count($rows); $i++) {
        $r = $rows[$i];
        $reg = trim((string) ($r[2] ?? ''));
        if ($reg === '' || ! is_numeric(trim((string) ($r[0] ?? '')))) {
            continue;
        }
        $out[$normalize($reg)] = [
            'name' => trim((string) ($r[1] ?? '')),
            'reg_number' => $normalize($reg),
            'model_year' => ($clean($r[3] ?? null) !== null) ? (int) $r[3] : null,
            'purchase_date' => $clean($r[4] ?? null),
            'registration_date' => $clean($r[5] ?? null),
            'engine_cc' => ($clean($r[6] ?? null) !== null) ? (int) $r[6] : null,
            'fuel_type' => $clean($r[7] ?? null),
            'purchase_value' => $cleanMoney($r[8] ?? null),
            'fitness_expires_at' => $clean($r[9] ?? null),
            'tax_token_expires_at' => $clean($r[10] ?? null),
            'insurance_expires_at' => $clean($r[11] ?? null),
            'route_permit_expires_at' => $clean($r[12] ?? null),
            'registration_paper_status' => strtolower($clean($r[13] ?? null) ?? 'ok'),
            '_unit' => $clean($r[14] ?? null),
        ];
    }

    return $out;
};

$extractSheet2 = static function (array $rows, callable $normalize, callable $clean, callable $cleanMoney): array {
    $out = [];
    for ($i = 4; $i < count($rows); $i++) {
        $r = $rows[$i];
        $reg = trim((string) ($r[2] ?? ''));
        if ($reg === '' || ! is_numeric(trim((string) ($r[0] ?? '')))) {
            continue;
        }
        $out[$normalize($reg)] = [
            'name' => trim((string) ($r[1] ?? '')),
            'reg_number' => $normalize($reg),
            'model_year' => ($clean($r[3] ?? null) !== null) ? (int) $r[3] : null,
            'purchase_date' => $clean($r[4] ?? null),
            'registration_date' => $clean($r[5] ?? null),
            'engine_cc' => ($clean($r[6] ?? null) !== null) ? (int) $r[6] : null,
            'fuel_type' => null,
            'purchase_value' => $cleanMoney($r[7] ?? null),
            'fitness_expires_at' => $clean($r[8] ?? null),
            'tax_token_expires_at' => $clean($r[9] ?? null),
            'insurance_expires_at' => $clean($r[10] ?? null),
            'route_permit_expires_at' => $clean($r[11] ?? null),
            'registration_paper_status' => strtolower($clean($r[12] ?? null) ?? 'ok'),
            '_unit' => $clean($r[13] ?? null),
        ];
    }

    return $out;
};

$base = dirname(__DIR__, 3) . '/storage/app/temp-vehicle-xls';
$s1 = $extractSheet1($parseCsv("{$base}/Sheet1.csv"), $normalize, $clean, $cleanMoney);
$s2 = $extractSheet2($parseCsv("{$base}/Sheet2.csv"), $normalize, $clean, $cleanMoney);

// Sheet1 is source of truth for paper expiry dates (Fitness / Tax / Insurance / Route).
// Sheet2 may supply clearer vehicle names and fill non-paper blanks only.
$merged = $s1;
foreach ($s2 as $reg => $row) {
    if (! isset($merged[$reg])) {
        $merged[$reg] = $row;
        continue;
    }

    if ($row['name'] !== '') {
        $merged[$reg]['name'] = $row['name'];
    }

    foreach (['purchase_value', 'model_year', 'engine_cc', 'purchase_date', 'registration_date', '_unit'] as $k) {
        if (($merged[$reg][$k] ?? null) === null || $merged[$reg][$k] === '') {
            $merged[$reg][$k] = $row[$k] ?? null;
        }
    }
}

$ordered = [];
foreach (array_keys($s1) as $reg) {
    $ordered[$reg] = $merged[$reg];
}
foreach ($merged as $reg => $row) {
    if (! isset($ordered[$reg])) {
        $ordered[$reg] = $row;
    }
}

$vehicles = [];
$categoryCounts = [];

foreach ($ordered as $row) {
    $name = trim(preg_replace('/\s+/', ' ', $row['name']) ?? '');
    [$category, $seats] = $mapCategoryAndSeats($name);
    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;

    $item = [
        'reg_number' => $row['reg_number'],
        'name' => $name,
        'vehicle_category' => $category,
        'unit' => $row['_unit'] ?? null,
        'model_year' => $row['model_year'],
        'purchase_date' => $row['purchase_date'],
        'registration_date' => $row['registration_date'],
        'engine_cc' => $row['engine_cc'],
        'fuel_type' => $row['fuel_type'],
        'purchase_value' => $row['purchase_value'],
        'fitness_expires_at' => $row['fitness_expires_at'],
        'tax_token_expires_at' => $row['tax_token_expires_at'],
        'insurance_expires_at' => $row['insurance_expires_at'],
        'route_permit_expires_at' => $row['route_permit_expires_at'],
        'type' => 'own',
        'passenger_capacity' => $seats,
        'status' => 'available',
        'registration_paper_status' => in_array($row['registration_paper_status'], ['ok', 'pending', 'expired'], true)
            ? $row['registration_paper_status']
            : 'ok',
    ];

    $vehicles[] = array_filter(
        $item,
        static fn ($v) => $v !== null && $v !== ''
    );
}

$outPath = dirname(__DIR__) . '/data/tms_vehicles.php';
$header = <<<'PHP'
<?php

/**
 * Vehicle register from "Vehicle Papers Update Information" spreadsheet
 * (Sheet1 + Sheet2 merged). Blank / missing cells are omitted.
 * vehicle_category and passenger_capacity are set from model name.
 * unit is Sheet1 Owner (NCL / HAL / BD Com / NFL / DHL) for factory mapping.
 * Allocated user and driver are assigned later in admin.
 *
 * Regenerate:
 *   php database/seeders/scripts/regen_tms_vehicles_from_csv.php
 */

return 
PHP;

file_put_contents($outPath, $header . var_export($vehicles, true) . ";\n");

echo 'Wrote ' . count($vehicles) . " vehicles to {$outPath}\n";
echo 'Sheet1: ' . count($s1) . ' | Sheet2: ' . count($s2) . ' | Sheet2-only added: ' . count(array_diff_key($s2, $s1)) . "\n";
echo "Categories:\n";
foreach ($categoryCounts as $cat => $count) {
    echo "  {$cat}: {$count}\n";
}
