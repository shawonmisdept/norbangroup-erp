<?php

namespace App\Support;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><sub><sup><ul><ol><li><h1><h2><h3><h4><h5><h6><span><a><blockquote><hr><table><thead><tbody><tr><th><td>';

    public static function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = trim($html);
        if ($html === '' || $html === '<p>&nbsp;</p>' || $html === '<p><br></p>') {
            return null;
        }

        $clean = strip_tags($html, self::ALLOWED_TAGS);
        $clean = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/href\s*=\s*("\s*javascript:[^"]*"|\'\s*javascript:[^\']*\')/iu', 'href="#"', $clean) ?? $clean;

        return trim($clean) ?: null;
    }
}
