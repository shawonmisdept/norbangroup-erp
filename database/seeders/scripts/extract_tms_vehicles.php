<?php

$base = dirname(__DIR__, 3) . '/storage/app/temp-xlsx';

$sst = simplexml_load_file("{$base}/xl/sharedStrings.xml");
$strings = [];

foreach ($sst->si as $si) {
    $strings[] = trim((string) ($si->t ?? ''));
}

$sheet = simplexml_load_file("{$base}/xl/worksheets/sheet1.xml");
$rows = [];

foreach ($sheet->sheetData->row as $row) {
    $cells = [];

    foreach ($row->c as $cell) {
        $ref = (string) $cell['r'];
        $col = preg_replace('/[0-9]+/', '', $ref);
        $value = (string) $cell->v;

        if ((string) $cell['t'] === 's') {
            $value = $strings[(int) $value] ?? '';
        }

        $cells[$col] = trim($value);
    }

    $rows[] = $cells;
}

$header = $rows[0] ?? [];
$vehicles = [];

for ($i = 1, $count = count($rows); $i < $count; $i++) {
    $row = $rows[$i];

    if (($row['C'] ?? '') === '') {
        continue;
    }

    $vehicles[] = [
        'unit' => $row['A'] ?? 'Head Office',
        'name' => $row['B'] ?? 'Toyota',
        'reg_number' => $row['C'],
        'type' => strtolower($row['D'] ?? 'own'),
        'fuel_type' => strtolower($row['E'] ?? 'gas'),
        'passenger_capacity' => (int) ($row['F'] ?? 5),
        'status' => strtolower($row['G'] ?? 'available'),
    ];
}

$output = "<?php\n\nreturn " . var_export($vehicles, true) . ";\n";
file_put_contents(dirname(__DIR__) . '/data/tms_vehicles.php', $output);

echo 'Exported ' . count($vehicles) . " vehicles\n";
