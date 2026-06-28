<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class RelationDisplay
{
    public static function label(?Model $record, string $attribute = 'name', ?string $withPath = null): string
    {
        if (! $record) {
            return '';
        }

        $label = (string) ($record->{$attribute} ?? '');

        if (! $withPath) {
            return $label;
        }

        $suffix = self::resolvePath($record, $withPath);

        return $suffix !== '' ? "{$label} — {$suffix}" : $label;
    }

    public static function resolvePath(?Model $record, string $path): string
    {
        $current = $record;

        foreach (explode('.', $path) as $segment) {
            if (! $current instanceof Model) {
                return '';
            }

            $current = $current->{$segment};
        }

        return $current === null ? '' : (string) $current;
    }
}
