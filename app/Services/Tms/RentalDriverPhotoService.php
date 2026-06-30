<?php

namespace App\Services\Tms;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RentalDriverPhotoService
{
    public const SIZE = 180;

    public static function store(UploadedFile $file, ?string $oldPath = null): string
    {
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $filename = 'rental-drivers/photos/' . uniqid('rdv_', true) . '.jpg';
        $fullPath = Storage::disk('public')->path($filename);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $source = self::loadImage($file->getRealPath(), $file->getMimeType());
        $resized = imagecreatetruecolor(self::SIZE, self::SIZE);

        imagecopyresampled(
            $resized,
            $source,
            0, 0, 0, 0,
            self::SIZE,
            self::SIZE,
            imagesx($source),
            imagesy($source)
        );

        imagejpeg($resized, $fullPath, 90);

        imagedestroy($source);
        imagedestroy($resized);

        return $filename;
    }

    private static function loadImage(string $path, string $mime)
    {
        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            default => false,
        };

        if (! $image) {
            throw new RuntimeException('Unsupported image format.');
        }

        return $image;
    }
}
