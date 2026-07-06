<?php

namespace App\Support;

class KbMarkdown
{
    public static function toHtml(string $markdown): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        $html = [];
        $inTable = false;
        $inList = false;
        $listType = 'ul';
        $inCode = false;
        $codeLines = [];

        foreach ($lines as $line) {
            if (preg_match('/^```/', $line)) {
                if ($inCode) {
                    $html[] = '<pre><code>' . e(implode("\n", $codeLines)) . '</code></pre>';
                    $codeLines = [];
                    $inCode = false;
                } else {
                    $inCode = true;
                }

                continue;
            }

            if ($inCode) {
                $codeLines[] = $line;

                continue;
            }

            if (trim($line) === '') {
                $html = array_merge($html, self::closeBlocks($inTable, $inList, $listType));
                $inTable = false;
                $inList = false;

                continue;
            }

            if (preg_match('/^---+$/', trim($line))) {
                $html = array_merge($html, self::closeBlocks($inTable, $inList, $listType));
                $inTable = false;
                $inList = false;
                $html[] = '<hr>';

                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
                $html = array_merge($html, self::closeBlocks($inTable, $inList, $listType));
                $inTable = false;
                $inList = false;
                $level = strlen($m[1]);
                $html[] = '<h' . $level . '>' . self::inline($m[2]) . '</h' . $level . '>';

                continue;
            }

            if (str_contains($line, '|') && preg_match('/^\|/', trim($line))) {
                if (preg_match('/^\|[\s\-:|]+\|$/', trim($line))) {
                    continue;
                }

                if (! $inTable) {
                    $html = array_merge($html, self::closeBlocks(false, $inList, $listType));
                    $inList = false;
                    $html[] = '<table><tbody>';
                    $inTable = true;
                }

                $cells = array_map('trim', explode('|', trim($line, '|')));
                $html[] = '<tr>' . implode('', array_map(
                    fn (string $cell) => '<td>' . self::inline($cell) . '</td>',
                    $cells,
                )) . '</tr>';

                continue;
            }

            if ($inTable) {
                $html[] = '</tbody></table>';
                $inTable = false;
            }

            if (preg_match('/^(\*|-|\d+\.)\s+(.+)$/', ltrim($line), $m)) {
                $type = str_contains($m[1], '.') ? 'ol' : 'ul';

                if (! $inList || $listType !== $type) {
                    if ($inList) {
                        $html[] = $listType === 'ol' ? '</ol>' : '</ul>';
                    }
                    $html[] = $type === 'ol' ? '<ol>' : '<ul>';
                    $inList = true;
                    $listType = $type;
                }

                $html[] = '<li>' . self::inline($m[2]) . '</li>';

                continue;
            }

            if ($inList) {
                $html[] = $listType === 'ol' ? '</ol>' : '</ul>';
                $inList = false;
            }

            $html[] = '<p>' . self::inline($line) . '</p>';
        }

        if ($inCode && $codeLines !== []) {
            $html[] = '<pre><code>' . e(implode("\n", $codeLines)) . '</code></pre>';
        }

        $html = array_merge($html, self::closeBlocks($inTable, $inList, $listType));

        return implode("\n", $html);
    }

    /** @return list<string> */
    private static function closeBlocks(bool $inTable, bool $inList, string $listType): array
    {
        $out = [];

        if ($inTable) {
            $out[] = '</tbody></table>';
        }

        if ($inList) {
            $out[] = $listType === 'ol' ? '</ol>' : '</ul>';
        }

        return $out;
    }

    private static function inline(string $text): string
    {
        $text = e($text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;
        $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $text) ?? $text;

        return $text;
    }
}
