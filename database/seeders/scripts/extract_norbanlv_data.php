<?php

/**
 * One-off helper: extracts master data arrays from norbanlv seeders into local data files.
 * Run: php database/seeders/scripts/extract_norbanlv_data.php
 */

$norbanlv = 'C:/wamp64/www/norbanlv/database/seeders';
$outDir   = __DIR__ . '/../data';

if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

function extractArrayBlock(string $content, string $varName): ?string
{
    $needle = '$' . $varName . ' = [';
    $start  = strpos($content, $needle);
    if ($start === false) {
        return null;
    }

    $start += strlen('$' . $varName . ' = ');
    $depth  = 0;
    $len    = strlen($content);
    $inStr  = false;
    $escape = false;

    for ($i = $start; $i < $len; $i++) {
        $ch = $content[$i];

        if ($inStr) {
            if ($escape) {
                $escape = false;
                continue;
            }
            if ($ch === '\\') {
                $escape = true;
                continue;
            }
            if ($ch === "'") {
                $inStr = false;
            }
            continue;
        }

        if ($ch === "'") {
            $inStr = true;
            continue;
        }

        if ($ch === '[') {
            $depth++;
        } elseif ($ch === ']') {
            $depth--;
            if ($depth === 0) {
                return substr($content, $start, $i - $start + 1);
            }
        }
    }

    return null;
}

function writeDataFile(string $path, string $arrayCode): void
{
    $content = "<?php\n\nreturn {$arrayCode};\n";
    file_put_contents($path, $content);
    echo 'Wrote ' . basename($path) . ' (' . number_format(strlen($content)) . " bytes)\n";
}

// Fabric types
$stylingPaths = [
    "$norbanlv/StylingDetailsModulesSeeder.php",
    'C:/Users/shawo/.cursor/projects/c-wamp64-www-order-portal/uploads/c__wamp64_www_norbanlv_database_seeders_StylingDetailsModulesSeeder-L170-L378-0.php',
];
foreach ($stylingPaths as $stylingPath) {
    if (! file_exists($stylingPath)) {
        continue;
    }
    $styling = file_get_contents($stylingPath);
    $fabric  = extractArrayBlock($styling, 'fabricTypes');
    if (! $fabric && str_starts_with(ltrim($styling), '$fabricTypes')) {
        $fabric = preg_replace('/^\$fabricTypes\s*=\s*/', '', rtrim($styling), 1);
        $fabric = rtrim($fabric, ";\n\r\t ");
    }
    if ($fabric) {
        writeDataFile("$outDir/fabric_types.php", $fabric);
        break;
    }
}

// Compositions
$fabrics = file_get_contents("$norbanlv/FabricsModulesSeeder.php");
$comps   = extractArrayBlock($fabrics, 'compositions');
if ($comps) {
    writeDataFile("$outDir/compositions.php", $comps);
}

// Accessories
$accFile = file_get_contents("$norbanlv/AccessoriesItemSeeder.php");
$acc     = extractArrayBlock($accFile, 'accessories');
if ($acc) {
    writeDataFile("$outDir/accessories_items.php", $acc);
}

// Body parts
$parts = file_get_contents("$norbanlv/PartSeeder.php");
$partsArray = extractArrayBlock($parts, 'parts');
if ($partsArray) {
    writeDataFile("$outDir/item_body_parts.php", $partsArray);
}

// Items + Order types from StyleModulesSeeder (create calls)
$style = file_get_contents("$norbanlv/StyleModulesSeeder.php");

preg_match_all(
    "/OrderType::create\(\[(.*?)\]\);/s",
    $style,
    $orderMatches
);
$orders = [];
foreach ($orderMatches[1] as $block) {
    $orders[] = eval('return [' . $block . '];');
}
writeDataFile("$outDir/order_types.php", var_export($orders, true));

preg_match_all(
    "/Item::create\(\[(.*?)\]\);/s",
    $style,
    $itemMatches
);
$items = [];
foreach ($itemMatches[1] as $block) {
    $items[] = eval('return [' . $block . '];');
}
writeDataFile("$outDir/items.php", var_export($items, true));

// Sustainabilities from FabricsModulesSeeder
preg_match_all(
    "/Sustainability::create\(\[(.*?)\]\);/s",
    $fabrics,
    $susMatches
);
$sustainabilities = [];
foreach ($susMatches[1] as $block) {
    $sustainabilities[] = eval('return [' . $block . '];');
}
writeDataFile("$outDir/sustainabilities.php", var_export($sustainabilities, true));

echo "Done.\n";
