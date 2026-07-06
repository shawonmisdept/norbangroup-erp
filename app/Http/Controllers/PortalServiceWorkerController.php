<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PortalServiceWorkerController extends Controller
{
    public function employee(): BinaryFileResponse
    {
        return $this->serve('pwa/employee-sw.js', '/employee/');
    }

    public function rental(): BinaryFileResponse
    {
        return $this->serve('pwa/rental-sw.js', '/rental/');
    }

    private function serve(string $relativePath, string $allowedScope): BinaryFileResponse
    {
        $path = public_path($relativePath);

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type'           => 'application/javascript; charset=UTF-8',
            'Service-Worker-Allowed' => $allowedScope,
        ]);
    }
}
