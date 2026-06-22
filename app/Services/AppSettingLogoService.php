<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AppSettingLogoService
{
    public static function store(UploadedFile $file, string $type, ?string $oldPath = null): string
    {
        if ($oldPath) {
            static::delete($oldPath);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = $type . '_' . uniqid('', true) . '.' . $extension;

        $file->storeAs('settings/logos', $filename, 'public');

        return 'settings/logos/' . $filename;
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
