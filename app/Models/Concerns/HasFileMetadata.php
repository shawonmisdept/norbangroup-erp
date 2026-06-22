<?php

namespace App\Models\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

trait HasFileMetadata
{
    public function normalizedFiles(string $type): Collection
    {
        $files = $type === 'techpack'
            ? ($this->techpack_files ?? [])
            : ($this->artwork_files ?? []);

        return collect($files)->values()->map(function ($file, $index) {
            if (is_string($file)) {
                return [
                    'path'          => $file,
                    'original_name' => basename($file),
                    'mime'          => Storage::disk('public')->mimeType($file),
                    'size'          => Storage::disk('public')->size($file),
                    'index'         => $index,
                ];
            }

            return [
                'path'          => $file['path'],
                'original_name' => $file['original_name'] ?? basename($file['path']),
                'mime'          => $file['mime'] ?? Storage::disk('public')->mimeType($file['path']),
                'size'          => $file['size'] ?? Storage::disk('public')->size($file['path']),
                'index'         => $index,
            ];
        });
    }

    public static function isPreviewable(?string $mime): bool
    {
        if (! $mime) {
            return false;
        }

        return str_starts_with($mime, 'image/') || $mime === 'application/pdf';
    }

    public static function storeUploadedFiles(array $files, string $directory): array
    {
        $stored = [];

        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $stored[] = [
                'path'          => $file->store($directory, 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime'          => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ];
        }

        return $stored;
    }

    public static function resolveFilePath(mixed $file): string
    {
        return is_string($file) ? $file : ($file['path'] ?? '');
    }
}
