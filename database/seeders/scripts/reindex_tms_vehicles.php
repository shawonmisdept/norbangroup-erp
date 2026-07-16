<?php

$path = __DIR__ . '/../data/tms_vehicles.php';
$rows = require $path;
$rows = array_values($rows);

file_put_contents($path, "<?php\n\nreturn " . var_export($rows, true) . ";\n");

echo count($rows) . ' vehicles reindexed' . PHP_EOL;

$regs = array_column($rows, 'reg_number');
foreach (['DM-GA-30-0062', 'DM-KHA-23-5772', 'DM-U-4801'] as $reg) {
    echo in_array($reg, $regs, true) ? "OK {$reg}\n" : "MISSING {$reg}\n";
}
