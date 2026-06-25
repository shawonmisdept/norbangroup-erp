<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmsPushToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('hrm.adms.push_token');

        if (! $expected) {
            abort(503, 'ADMS push endpoint is not configured.');
        }

        $provided = $request->bearerToken()
            ?? $request->header('X-Adms-Token')
            ?? $request->input('token');

        if (! hash_equals($expected, (string) $provided)) {
            abort(401, 'Invalid ADMS push token.');
        }

        return $next($request);
    }
}
