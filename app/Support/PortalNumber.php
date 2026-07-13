<?php

namespace App\Support;

class PortalNumber
{
    /** Format a quantity without unnecessary trailing zeros (e.g. 200.000 → 200). */
    public static function quantity(float|string|int|null $value, int $maxDecimals = 3): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $formatted = number_format((float) $value, $maxDecimals, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
