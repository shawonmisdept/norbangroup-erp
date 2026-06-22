<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnyMasterViewPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyMasterViewPermission()) {
            abort(403, 'You do not have permission to access master data.');
        }

        return $next($request);
    }
}
