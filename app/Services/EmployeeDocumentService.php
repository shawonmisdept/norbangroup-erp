<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentService
{
    public const MAX_KB = 5120;

    public static function store(UploadedFile $file, string $folder, ?string $oldPath = null): string
    {
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename = sprintf('employees/%s/%s.%s', $folder, uniqid('doc_', true), $extension);

        Storage::disk('public')->putFileAs(
            dirname($filename),
            $file,
            basename($filename)
        );

        return $filename;
    }
}
