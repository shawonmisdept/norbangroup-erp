<?php

/**
 * Compare one vehicle sheet in Excel vs tms_maintenance.php seed.
 * Usage: php database/seeders/scripts/compare_excel_vs_seed.php DM-GHA-11-8402 8402
 */

$reg = strtoupper($argv[1] ?? 'DM-GHA-11-8402');
$sheetSuffix = trim($argv[2] ?? '8402');
$base = dirname(__DIR__, 3) . '/storage/app/temp-maint-xlsx';

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
    return preg_match('/^Month Of\s*:/i', $row['A'] ?? '') === 1;
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
    foreach (['Sub Total', 'Summery Of', 'Summary Of', 'Car No', 'Bill / Invoice No', 'Bill/Invoice No'] as $needle) {
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

function parseMaintenanceSheetWithMonths(array $rows): array
{
    $bills = [];
    $months = [];
    $currentBill = null;
    $started = false;
    $currentMonthLabel = null;
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
            $currentMonthLabel = trim(preg_replace('/^Month Of\s*:\s*/i', '', $billNo) ?? $billNo);
            $currentMonthDate = parseMonthDate($billNo);
            continue;
        }

        if (isSubTotalRow($row)) {
            if ($currentBill !== null && $currentBill['items'] !== []) {
                $bills[] = $currentBill;
                $currentBill = null;
            }
            $excelSubTotal = (float) preg_replace('/[^\d.\-]/', '', (string) $amountRaw);
            if ($currentMonthLabel !== null) {
                $itemSum = 0.0;
                foreach ($bills as $bill) {
                    if (($bill['month_of'] ?? null) === $currentMonthLabel) {
                        $itemSum += array_sum(array_column($bill['items'], 'amount'));
                    }
                }
                $months[$currentMonthLabel] = [
                    'excel_sub_total' => round($excelSubTotal, 2),
                    'computed_items_sum' => round($itemSum, 2),
                    'diff' => round($excelSubTotal - $itemSum, 2),
                ];
            }
            continue;
        }

        if (isHeaderOrMetaRow($billNo)) {
            if ($currentBill !== null && $currentBill['items'] !== []) {
                $bills[] = $currentBill;
                $currentBill = null;
            }
            continue;
        }

        if ($billNo !== '') {
            if ($currentBill !== null && $currentBill['items'] !== []) {
                $bills[] = $currentBill;
            }
            $currentBill = [
                'bill_no' => $billNo,
                'bill_date' => excelDateToIso($billDateRaw) ?? $currentMonthDate,
                'workshop_name' => $workshop,
                'month_of' => $currentMonthLabel,
                'paid_by' => 'company',
                'items' => [],
            ];
        }

        if ($currentBill === null || $itemName === '') {
            continue;
        }

        $amount = (float) $amountRaw;
        if ($amount <= 0) {
            continue;
        }

        $currentBill['items'][] = [
            'item_name' => $itemName,
            'quantity' => $qtyRaw === '' ? null : (float) $qtyRaw,
            'unit' => $unit !== '' ? $unit : null,
            'amount' => round($amount, 2),
        ];
    }

    if ($currentBill !== null && $currentBill['items'] !== []) {
        $bills[] = $currentBill;
    }

    foreach ($bills as &$bill) {
        $bill['total_amount'] = round(array_sum(array_column($bill['items'], 'amount')), 2);
    }
    unset($bill);

    return ['bills' => $bills, 'months' => $months];
}

$workbook = simplexml_load_file("{$base}/xl/workbook.xml");
$rels = simplexml_load_file("{$base}/xl/_rels/workbook.xml.rels");
$relMap = [];
foreach ($rels->Relationship as $rel) {
    $relMap[(string) $rel['Id']] = (string) $rel['Target'];
}

$sheetPath = null;
foreach ($workbook->sheets->sheet as $sheet) {
    $name = trim((string) $sheet['name']);
    if ($name === $sheetSuffix) {
        $relId = (string) ($sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? '');
        $target = $relMap[$relId] ?? null;
        if ($target) {
            $sheetPath = "{$base}/xl/" . str_replace('\\', '/', $target);
        }
        break;
    }
}

if (! $sheetPath || ! is_file($sheetPath)) {
    fwrite(STDERR, "Sheet {$sheetSuffix} not found\n");
    exit(1);
}

$excel = parseMaintenanceSheetWithMonths(parseSheetRows($sheetPath, loadStrings($base)));
$seed = require __DIR__ . '/../data/tms_maintenance.php';
$seedBills = $seed[$reg]['bills'] ?? [];

echo "=== EXCEL vs SEED: {$reg} (sheet {$sheetSuffix}) ===" . PHP_EOL . PHP_EOL;

$excelTotal = round(array_sum(array_column($excel['bills'], 'total_amount')), 2);
$seedTotal = round(array_sum(array_map(static fn ($b) => (float) ($b['total_amount'] ?? 0), $seedBills)), 2);

echo 'Excel bills (fresh parse): ' . count($excel['bills']) . ' | BDT ' . number_format($excelTotal, 2) . PHP_EOL;
echo 'Seed bills: ' . count($seedBills) . ' | BDT ' . number_format($seedTotal, 2) . PHP_EOL;
echo 'Grand total diff (seed - excel): ' . number_format($seedTotal - $excelTotal, 2) . PHP_EOL . PHP_EOL;

echo '=== EXCEL INTERNAL: Sub Total row vs sum(items) ===' . PHP_EOL;
foreach ($excel['months'] as $label => $row) {
    $flag = abs($row['diff']) > 0.01 ? ' ***' : '';
    echo sprintf(
        "  %-12s | Excel SubTotal: %12s | Items sum: %12s | diff: %+10.2f%s\n",
        $label,
        number_format($row['excel_sub_total'], 2),
        number_format($row['computed_items_sum'], 2),
        $row['diff'],
        $flag
    );
}

echo PHP_EOL . '=== PORTAL (bill_date month) vs EXCEL Sub Total ===' . PHP_EOL;
$seedByBillDateMonth = [];
foreach ($seedBills as $bill) {
    $m = substr((string) ($bill['bill_date'] ?? ''), 0, 7);
    $seedByBillDateMonth[$m] = ($seedByBillDateMonth[$m] ?? 0) + (float) ($bill['total_amount'] ?? 0);
}

foreach ($excel['months'] as $label => $row) {
    $seedDateMonth = null;
    if (preg_match('/^([A-Za-z]+)-(\d{4})$/', $label, $m)) {
        $ts = strtotime("{$m[1]} 1 {$m[2]}");
        $seedDateMonth = $ts ? date('Y-m', $ts) : null;
    }
    $seedSum = $seedDateMonth ? round($seedByBillDateMonth[$seedDateMonth] ?? 0, 2) : 0;
    $portalVsExcel = round($seedSum - $row['excel_sub_total'], 2);
    $flag = abs($portalVsExcel) > 0.01 ? ' *** MISMATCH ***' : '';
    echo sprintf(
        "  %-12s | Excel: %12s | Portal: %12s | diff: %+10.2f%s\n",
        $label,
        number_format($row['excel_sub_total'], 2),
        number_format($seedSum, 2),
        $portalVsExcel,
        $flag
    );
}

echo PHP_EOL . '=== RENAMED BILLS IN SEED (Excel bill_no differs) ===' . PHP_EOL;
$seedByNo = [];
foreach ($seedBills as $bill) {
    $seedByNo[(string) $bill['bill_no']] = $bill;
}
$excelNos = array_map(static fn ($b) => (string) $b['bill_no'], $excel['bills']);
foreach ($seedByNo as $no => $bill) {
    if (! in_array($no, $excelNos, true)) {
        $plain = preg_replace('/\s*\(\d+\)$/', '', $no);
        $match = in_array($plain, $excelNos, true) ? " (was {$plain} in Excel)" : '';
        echo "  {$no} | {$bill['bill_date']} | " . number_format((float) $bill['total_amount'], 2) . $match . PHP_EOL;
    }
}
