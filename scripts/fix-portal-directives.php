<?php

$root = __DIR__ . '/../resources/views';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

foreach ($iterator as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);
    $fixed = preg_replace('/@portal([A-Za-z]+)\((\$[^)?]+)\?\)/', '@portal$1($2)', $content);

    if ($fixed !== $content) {
        file_put_contents($path, $fixed);
        echo $path . PHP_EOL;
    }
}
