<?php

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/../resources/views')
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();

    if (str_contains($path, 'confirm-dialog.blade.php')) {
        continue;
    }

    $content = file_get_contents($path);
    $original = $content;

    $content = preg_replace(
        '/\s+onsubmit="return confirm\(@js\((.*?)\)\)"/',
        ' data-confirm={$1}',
        $content
    );

    $content = preg_replace_callback(
        '/\s+onsubmit="return confirm\(\'(.*?)\'\)"/',
        fn (array $matches) => ' data-confirm="' . htmlspecialchars($matches[1], ENT_QUOTES) . '"',
        $content
    );

    $content = preg_replace_callback(
        '/ onclick="return confirm\(\'(.*?)\'\)"/',
        function (array $matches) use (&$content) {
            return '';
        },
        $content
    );

    $content = preg_replace_callback(
        '/<form(\s[^>]*method="POST"[^>]*)>((?:(?!<form).)*?)<button type="submit"([^>]*)>(.*?)<\/button>/s',
        function (array $matches) {
            $attrs = $matches[1];
            $body = $matches[2];
            $btnAttrs = $matches[3];
            $btnText = $matches[4];

            if (str_contains($attrs, 'data-confirm')) {
                return $matches[0];
            }

            if (preg_match('/onclick="return confirm\(\'(.*?)\'\)"/', $btnAttrs, $confirm)) {
                $btnAttrs = preg_replace('/\s*onclick="return confirm\(\'.*?\'\)"/', '', $btnAttrs);
                $attrs .= ' data-confirm="' . htmlspecialchars($confirm[1], ENT_QUOTES) . '"';

                return '<form' . $attrs . '>' . $body . '<button type="submit"' . $btnAttrs . '>' . $btnText . '</button>';
            }

            return $matches[0];
        },
        $content
    );

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo basename($path) . PHP_EOL;
    }
}
