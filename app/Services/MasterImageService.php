<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MasterImageService
{
    public static function store(UploadedFile $file, string $directory, int $size = 400, ?string $oldPath = null): string
    {
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $filename = $directory . '/' . uniqid('img_', true) . '.jpg';
        $fullPath = Storage::disk('public')->path($filename);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $source = self::loadImage($file->getRealPath(), $file->getMimeType());
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min($size / $width, $size / $height, 1);
        $newW = (int) max(1, round($width * $scale));
        $newH = (int) max(1, round($height * $scale));

        $resized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
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
