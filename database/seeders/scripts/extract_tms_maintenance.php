<?php

/**
 * Extract maintenance bills from Vehicle Maintenance Information.xlsx
 * Usage: php database/seeders/scripts/extract_tms_maintenance.php
 */

$base = dirname(__DIR__, 3) . '/storage/app/temp-maint-xlsx';
$vehicles = require dirname(__DIR__) . '/data/tms_vehicles.php';

// Build suffix -> reg_number map (last segment after final hyphen)
$suffixToReg = [];
foreach ($vehicles as $vehicle) {
    $parts = explode('-', $vehicle['reg_number']);
    $suffix = end($parts);
    $suffixToReg[ltrim($suffix, '0') ?: $suffix] = $vehicle['reg_number'];
    $suffixToReg[$suffix] = $vehicle['reg_number'];
}

function loadStrings(string $base): array
{
    $sst = simplexml_load_file("{$base}/xl/sharedStrings.xml");
    $strings = [];

    foreach ($sst->si as $si) {
        if (isset($si->t)) {
            $strings[] = trim((string) $si->t);
        } else {
            $parts = [];
            foreach ($si->xpath('.//t') as $t) {
                $parts[] = (string) $t;
            }
            $strings[] = trim(implode('', $parts));
        }
    }

    return $strings;
}

function colIndex(string $col): int
{
    $index = 0;
    foreach (str_split($col) as $char) {
        $index = $index * 26 + (ord($char) - 64);
    }

    return $index;
}

function parseSheetRows(string $path, array $strings): array
{
    $sheet = simplexml_load_file($path);
    $rows = [];

    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
            $col = $m[1];
            $value = (string) $cell->v;
            if ((string) $cell['t'] === 's') {
                $value = $strings[(int) $value] ?? '';
            }
            $cells[$col] = trim($value);
        }
        if ($cells !== []) {
            $rows[] = $cells;
        }
    }

    return $rows;
}

function excelDateToIso(string $value): ?string
{
    if ($value === '' || ! is_numeric($value)) {
        return null;
    }

    $serial = (float) $value;
    if ($serial < 1000) {
        return null;
    }

    $unix = (int) round(($serial - 25569) * 86400);

    return gmdate('Y-m-d', $unix);
}

function isMonthHeader(array $row): bool
{
    $a = $row['A'] ?? '';

    return preg_match('/^Month Of\s*:/i', $a) === 1;
}

function isSubTotalRow(array $row): bool
{
    return ($row['A'] ?? '') === 'Sub Total';
}

function isHeaderOrMetaRow(string $value): bool
{
    if ($value === '') {
        return false;
    }

    if (preg_match('/^Month Of\s*:/i', $value)) {
        return true;
    }

    $needles = [
        'Sub Total',
        'Summery Of',
        'Summary Of',
        'Car No',
        'Bill / Invoice No',
        'Bill/Invoice No',
    ];

    foreach ($needles as $needle) {
        if (str_starts_with($value, $needle)) {
            return true;
        }
    }

    return false;
}

function parseMonthDate(string $value): ?string
{
    if (! preg_match('/^Month Of\s*:\s*([A-Za-z]+)-(\d{4})/i', $value, $match)) {
        return null;
    }

    $timestamp = strtotime("{$match[1]} 1 {$match[2]}");

    return $timestamp ? date('Y-m-d', $timestamp) : null;
}

function parseMaintenanceSheet(array $rows): array
{
    $bills = [];
    $currentBill = null;
    $started = false;
    $currentMonthDate = null;

    foreach ($rows as $row) {
        $billNo = trim($row['A'] ?? '');
        $billDateRaw = $row['B'] ?? '';
        $workshop = trim($row['C'] ?? '');
        $itemName = trim($row['D'] ?? '');
        $qtyRaw = $row['E'] ?? '';
        $unit = trim($row['F'] ?? '');
        $amountRaw = $row['G'] ?? '';

        if (! $started) {
            if ($billNo === 'Bill / Invoice No' || $billNo === 'Bill/Invoice No') {
                $started = true;
            }

            continue;
        }

        if (isMonthHeader($row)) {
            $currentMonthDate = parseMonthDate($billNo);

            continue;
        }

        if (isSubTotalRow($row) || isHeaderOrMetaRow($billNo)) {
            if (isSubTotalRow($row) && $currentBill !== null && $currentBill['items'] !== []) {
                $bills[] = $currentBill;
                $currentBill = null;
            }

            continue;
        }

        if ($billNo !== '') {
            if ($currentBill !== null && $currentBill['items'] !== []) {
                $bills[] = $currentBill;
            }

            $billDate = excelDateToIso($billDateRaw) ?? $currentMonthDate;

            $currentBill = [
                'bill_no' => $billNo,
                'bill_date' => $billDate,
                'workshop_name' => $workshop,
                'paid_by' => 'company',
                'items' => [],
            ];
        }

        if ($currentBill === null || $itemName === '') {
            continue;
        }

        $qty = $qtyRaw === '' ? null : (float) $qtyRaw;
        $amount = (float) $amountRaw;

        if ($amount <= 0) {
            continue;
        }

        $currentBill['items'][] = [
            'item_name' => $itemName,
            'quantity' => $qty,
            'unit' => $unit !== '' ? $unit : null,
            'amount' => round($amount, 2),
        ];
    }

    if ($currentBill !== null && $currentBill['items'] !== []) {
        $bills[] = $currentBill;
    }

    $bills = array_values(array_filter($bills, fn (array $bill) => $bill['bill_date'] !== null && $bill['bill_date'] !== ''));

    foreach ($bills as &$bill) {
        $bill['total_amount'] = round(array_sum(array_column($bill['items'], 'amount')), 2);
    }
    unset($bill);

    return $bills;
}

function resolveRegNumber(string $sheetName, array $rows, array $suffixToReg): ?string
{
    $limit = min(5, count($rows));
    for ($i = 0; $i < $limit; $i++) {
        foreach ($rows[$i] as $value) {
            if (preg_match('/DM-[A-Z0-9]+-\d+-\d+/i', $value, $match)) {
                return strtoupper($match[0]);
            }
        }
    }

    $sheetName = trim($sheetName);
    $candidates = [$sheetName, ltrim($sheetName, '0'), str_pad(ltrim($sheetName, '0'), 4, '0', STR_PAD_LEFT)];

    foreach ($candidates as $suffix) {
        if (isset($suffixToReg[$suffix])) {
            return $suffixToReg[$suffix];
        }
    }

    return null;
}

$strings = loadStrings($base);
$workbook = simplexml_load_file("{$base}/xl/workbook.xml");
$rels = simplexml_load_file("{$base}/xl/_rels/workbook.xml.rels");

$relMap = [];
foreach ($rels->Relationship as $rel) {
    $relMap[(string) $rel['Id']] = (string) $rel['Target'];
}

$data = [];
$skippedSheets = [];
$processedRegs = [];
$duplicateSkipReg = 'DM-MA-11-6079';

foreach ($workbook->sheets->sheet as $sheet) {
    $sheetName = trim((string) $sheet['name']);
    $relAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $relId = (string) ($relAttrs['id'] ?? '');
    $target = $relMap[$relId] ?? null;

    if (! $target) {
        continue;
    }

    $sheetPath = "{$base}/xl/" . str_replace('\\', '/', $target);
    $rows = parseSheetRows($sheetPath, $strings);
    $regNumber = resolveRegNumber($sheetName, $rows, $suffixToReg);

    if (! $regNumber) {
        $skippedSheets[] = "Sheet \"{$sheetName}\": vehicle not found";
        continue;
    }

        if ($regNumber === $duplicateSkipReg && isset($processedRegs[$regNumber])) {
            $skippedSheets[] = "Sheet \"{$sheetName}\": duplicate {$regNumber} skipped";
            continue;
        }

    $bills = parseMaintenanceSheet($rows);

    if ($bills === []) {
        $skippedSheets[] = "Sheet \"{$sheetName}\" ({$regNumber}): no bills";
        continue;
    }

    if (! isset($data[$regNumber])) {
        $data[$regNumber] = ['bills' => []];
    }

    $data[$regNumber]['bills'] = array_merge($data[$regNumber]['bills'], $bills);
    $processedRegs[$regNumber] = true;
}

$output = "<?php\n\nreturn " . var_export($data, true) . ";\n";
file_put_contents(dirname(__DIR__) . '/data/tms_maintenance.php', $output);

$billCount = array_sum(array_map(fn ($v) => count($v['bills']), $data));
echo "Exported {$billCount} bills for " . count($data) . " vehicles\n";

if ($skippedSheets !== []) {
    echo "Skipped:\n";
    foreach ($skippedSheets as $line) {
        echo "  - {$line}\n";
    }
}
