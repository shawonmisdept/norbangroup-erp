<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class PortalDateTime
{
    public static function timezone(): string
    {
        return (string) config('app.timezone', 'UTC');
    }

    public static function inAppTimezone(CarbonInterface|string|null $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $tz = self::timezone();

        return $value instanceof CarbonInterface
            ? $value->copy()->timezone($tz)
            : Carbon::parse($value, $tz)->timezone($tz);
    }

    public static function date(CarbonInterface|string|null $value): string
    {
        return self::inAppTimezone($value)?->format('d M Y') ?? '—';
    }

    public static function time(CarbonInterface|string|null $value): string
    {
        return self::inAppTimezone($value)?->format('g:i A') ?? '—';
    }

    /** e.g. 06 Jul 2026 3:45 PM */
    public static function dateTime(CarbonInterface|string|null $value): string
    {
        return self::inAppTimezone($value)?->format('d M Y g:i A') ?? '—';
    }

    /** e.g. 06 Jul 3:45 PM */
    public static function dateTimeShort(CarbonInterface|string|null $value): string
    {
        return self::inAppTimezone($value)?->format('d M g:i A') ?? '—';
    }

    /** e.g. 06 Jul 2026, 3:45 PM */
    public static function dateCommaTime(CarbonInterface|string|null $value): string
    {
        return self::inAppTimezone($value)?->format('d M Y, g:i A') ?? '—';
    }

    /** e.g. 06 Jul 2026 3:45:08 PM */
    public static function dateTimeWithSeconds(CarbonInterface|string|null $value): string
    {
        return self::inAppTimezone($value)?->format('d M Y g:i:s A') ?? '—';
    }
}
