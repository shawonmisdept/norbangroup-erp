<?php

/**
 * Merge duplicate bill_no entries on same vehicle when date + workshop also match.
 * Different dates keep the same bill_no (unique key is vehicle + bill_no + bill_date).
 */

$path = __DIR__ . '/../data/tms_maintenance.php';
$data = require $path;

$merged = 0;

foreach ($data as $reg => &$vehicleData) {
    $normalized = [];
    $indexByKey = [];

    foreach ($vehicleData['bills'] ?? [] as $bill) {
        $billNo = trim((string) ($bill['bill_no'] ?? ''));
        if ($billNo === '') {
            continue;
        }

        $date = (string) ($bill['bill_date'] ?? '');
        $mergeKey = strtolower($billNo) . '|' . $date;

        if (isset($indexByKey[$mergeKey])) {
            $targetIndex = $indexByKey[$mergeKey];
            $normalized[$targetIndex]['items'] = array_merge(
                $normalized[$targetIndex]['items'] ?? [],
                $bill['items'] ?? []
            );
            $normalized[$targetIndex]['total_amount'] = round(array_sum(array_column(
                $normalized[$targetIndex]['items'],
                'amount'
            )), 2);
            $merged++;
            continue;
        }

        $bill['total_amount'] = round(array_sum(array_column($bill['items'] ?? [], 'amount')), 2);
        $indexByKey[$mergeKey] = count($normalized);
        $normalized[] = $bill;
    }

    $vehicleData['bills'] = array_values($normalized);
}
unset($vehicleData);

file_put_contents($path, "<?php\n\nreturn " . var_export($data, true) . ";\n");

echo "Merged duplicate bills: {$merged}" . PHP_EOL;

$dupes = 0;
foreach ($data as $reg => $vehicleData) {
    $seen = [];
    foreach ($vehicleData['bills'] as $bill) {
        $key = ($bill['bill_no'] ?? '') . '|' . ($bill['bill_date'] ?? '');
        if (isset($seen[$key])) {
            $dupes++;
            echo "Still duplicate: {$reg} | {$key}" . PHP_EOL;
        }
        $seen[$key] = true;
    }
}
echo "Remaining vehicle+bill_no+date duplicates: {$dupes}" . PHP_EOL;
