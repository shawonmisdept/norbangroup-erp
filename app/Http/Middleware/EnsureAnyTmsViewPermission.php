<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnyTmsViewPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyTmsViewPermission()) {
            abort(403, 'You do not have permission to access Transport Management.');
        }

        return $next($request);
    }
}
