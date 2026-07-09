<?php

namespace App\Support;

class NotificationUrl
{
    /** Relative app path for database notifications (host-agnostic). */
    public static function route(string $name, mixed $parameters = [], array $query = []): string
    {
        $path = route($name, $parameters, false);

        if ($query !== []) {
            $path .= (str_contains($path, '?') ? '&' : '?') . http_build_query($query);
        }

        return $path;
    }

    /** Normalize stored notification URLs to a relative path on the current host. */
    public static function resolve(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $parts = parse_url($url);

        if (! isset($parts['path'])) {
            return $url;
        }

        $path = $parts['path'];

        if (! empty($parts['query'])) {
            $path .= '?' . $parts['query'];
        }

        return $path;
    }
}
