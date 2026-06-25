<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnyHrmMasterViewPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyHrmMasterViewPermission()) {
            abort(403, 'You do not have permission to access HRM master data.');
        }

        return $next($request);
    }
}
