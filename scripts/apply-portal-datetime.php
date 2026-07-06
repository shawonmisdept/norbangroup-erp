<?php

$root = __DIR__ . '/../resources/views';

$patterns = [
    '/\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]\'"]+)->format\(\'H:i\'\)\s*\?\?\s*\'—\'\s*\}\}/' => '@portalTime($1)',
    '/\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]\'"]+)->format\(\'H:i\'\)\s*\}\}/' => '@portalTime($1)',
    '/\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]\'"]+)->format\(\'d M Y H:i:s\'\)\s*\}\}/' => '@portalDateTimeSeconds($1)',
    '/\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]\'"]+)->format\(\'d M Y, H:i\'\)\s*\?\?\s*\'—\'\s*\}\}/' => '@portalDateCommaTime($1)',
    '/\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]\'"]+)->format\(\'d M Y, H:i\'\)\s*\}\}/' => '@portalDateCommaTime($1)',
    '/\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]\'"]+)->format\(\'d M Y H:i\'\)\s*\?\?\s*\'—\'\s*\}\}/' => '@portalDateTime($1)',
    '/\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]\'"]+)->format\(\'d M Y H:i\'\)\s*\}\}/' => '@portalDateTime($1)',
    '/\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]\'"]+)->format\(\'d M H:i\'\)\s*\?\?\s*(?:\'—\'|\$[a-zA-Z0-9_>-]+)\s*\}\}/' => '@portalDateTimeShort($1)',
    '/\{\{\s*(\$[a-zA-Z0-9_>?\-\[\]\'"]+)->format\(\'d M H:i\'\)\s*\}\}/' => '@portalDateTimeShort($1)',
];

$manual = [
    $root . '/admin/hrm/attendance/daily.blade.php' => [
        ["{{ \$log->check_in?->format('H:i') ?? '—' }}", '@portalTime($log->check_in)'],
        ["{{ \$log->check_out?->format('H:i') ?? '—' }}", '@portalTime($log->check_out)'],
        ["{{ \$photoPunch->punched_at->format('g:i A') }}", '@portalTime($photoPunch->punched_at)'],
    ],
    $root . '/admin/masters/partials/column.blade.php' => [
        ['{{ $record->{$column} }}', '{{ \\App\\Support\\TimeInput::formatForDisplay($record->{$column}) }}'],
    ],
    $root . '/admin/hrm/attendance/manual-punch/form.blade.php' => [
        ["old('punch_time', \$punch->punched_at?->format('H:i') ?? '08:00')", "old('punch_time', isset(\$punch) ? \\App\\Support\\TimeInput::formatForInput(\$punch->punched_at) : '08:00')"],
    ],
];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$updated = 0;

foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php' && ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    if (! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);
    $original = $content;

    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }

    if (isset($manual[$path])) {
        foreach ($manual[$path] as [$search, $replace]) {
            $content = str_replace($search, $replace, $content);
        }
    }

    if ($content !== $original) {
        file_put_contents($path, $content);
        $updated++;
        echo str_replace($root . DIRECTORY_SEPARATOR, '', $path) . PHP_EOL;
    }
}

// Fix shift time columns if still raw
$columnPath = $root . '/admin/masters/partials/column.blade.php';
if (is_file($columnPath)) {
    $content = file_get_contents($columnPath);
    $needle = "@elseif(in_array(\$column, ['start_time', 'end_time', 'break_start_time', 'break_end_time'], true) && \$record->{\$column})";
    if (str_contains($content, $needle) && ! str_contains($content, 'formatForDisplay')) {
        $content = str_replace(
            '<span class="text-gray-600">{{ $record->{$column} }}</span>',
            '<span class="text-gray-600">{{ \\App\\Support\\TimeInput::formatForDisplay($record->{$column}) }}</span>',
            $content
        );
        file_put_contents($columnPath, $content);
        echo "column.blade.php (shift display)\n";
    }
}

echo "Updated {$updated} files\n";
