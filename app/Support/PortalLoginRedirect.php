<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;

class PortalLoginRedirect
{
    public static function afterLogin(string $pathPrefix, string $defaultUrl): RedirectResponse
    {
        $intended = session()->pull('url.intended');

        if ($intended) {
            $path = parse_url($intended, PHP_URL_PATH) ?? '';

            if (str_starts_with($path, $pathPrefix)) {
                return redirect()->to($intended);
            }
        }

        return redirect()->to($defaultUrl);
    }
}
