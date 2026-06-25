<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHrmMasterModulePermission
{
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $module = $request->route('module');

        if (! $module || ! config("hrm.modules.{$module}")) {
            abort(404);
        }

        $user = $request->user();
        $permission = "hrm.{$module}.{$action}";

        if (! $user || ! $user->hasPermission($permission)) {
            abort(403, 'You do not have permission to access this HRM module.');
        }

        return $next($request);
    }
}
