<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTmsGpsApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('tms.gps_api_token');

        if (! $expected) {
            abort(503, 'TMS GPS API is not configured.');
        }

        $provided = $request->bearerToken()
            ?? $request->header('X-Tms-Gps-Token')
            ?? $request->input('token');

        if (! hash_equals($expected, (string) $provided)) {
            abort(401, 'Invalid TMS GPS API token.');
        }

        return $next($request);
    }
}
