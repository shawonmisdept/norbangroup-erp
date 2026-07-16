<?php

$base = dirname(__DIR__, 3) . '/storage/app/temp-maint-xlsx';
$sheetPath = null;
$workbook = simplexml_load_file("{$base}/xl/workbook.xml");
$rels = simplexml_load_file("{$base}/xl/_rels/workbook.xml.rels");
$relMap = [];
foreach ($rels->Relationship as $rel) {
    $relMap[(string) $rel['Id']] = (string) $rel['Target'];
}
foreach ($workbook->sheets->sheet as $sheet) {
    if (trim((string) $sheet['name']) === '8402') {
        $relId = (string) ($sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? '');
        $sheetPath = "{$base}/xl/" . str_replace('\\', '/', $relMap[$relId]);
        break;
    }
}

$sst = simplexml_load_file("{$base}/xl/sharedStrings.xml");
$strings = [];
foreach ($sst->si as $si) {
    $strings[] = isset($si->t) ? trim((string) $si->t) : trim(implode('', array_map('strval', $si->xpath('.//t'))));
}

$sheet = simplexml_load_file($sheetPath);
$inSection = false;
$section = '';

foreach ($sheet->sheetData->row as $row) {
    $cells = [];
    foreach ($row->c as $cell) {
        preg_match('/^([A-Z]+)(\d+)$/', (string) $cell['r'], $m);
        $v = (string) $cell->v;
        if ((string) $cell['t'] === 's') {
            $v = $strings[(int) $v] ?? '';
        }
        $cells[$m[1]] = trim($v);
    }

    $a = $cells['A'] ?? '';
    if (preg_match('/^Month Of\s*:/i', $a)) {
        $section = $a;
        $inSection = true;
        echo "\n--- {$section} ---\n";
        continue;
    }

    if (! $inSection) {
        continue;
    }

    if (! preg_match('/(September-2024|May-2024|Novembery-2024|November-2024)/i', $section)) {
        continue;
    }

    if ($a === 'Sub Total' || $a === 'Bill / Invoice No' || $a === '') {
        if ($a === 'Sub Total') {
            echo sprintf("SUBTOTAL G=%s\n", $cells['G'] ?? '');
        }
        continue;
    }

    echo sprintf(
        "A=%s | B=%s | C=%s | D=%s | G=%s\n",
        $a,
        $cells['B'] ?? '',
        $cells['C'] ?? '',
        substr($cells['D'] ?? '', 0, 40),
        $cells['G'] ?? ''
    );
}
