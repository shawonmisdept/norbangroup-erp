<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnyHrmViewPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyHrmViewPermission()) {
            abort(403, 'You do not have permission to access HRM.');
        }

        return $next($request);
    }
}
