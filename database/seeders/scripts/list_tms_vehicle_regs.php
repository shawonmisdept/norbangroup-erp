<?php

$v = require __DIR__ . '/../data/tms_vehicles.php';
echo count($v) . PHP_EOL;
foreach ($v as $r) {
    echo $r['reg_number'] . PHP_EOL;
}
