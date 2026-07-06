<?php

namespace App\Support;

use Carbon\Carbon;

class TimeInput
{
    /** Format for HTML `<input type="time">` (24h, H:i). */
    public static function formatForInput(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse((string) $value)->format('H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /** Normalize submitted time to H:i for validation and storage. */
    public static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable) {
            if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $matches)) {
                return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
            }
        }

        return null;
    }

    /** User-facing clock time (12h AM/PM). */
    public static function formatForDisplay(mixed $value): string
    {
        return PortalDateTime::time($value);
    }
}
