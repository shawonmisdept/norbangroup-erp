<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserFactoryScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isUnitScoped()) {
            return $next($request);
        }

        if ($request->has('factory_id') && $request->input('factory_id') !== ''
            && (int) $request->input('factory_id') !== (int) $user->factory_id) {
            abort(403, 'You can only access data for your assigned factory unit.');
        }

        return $next($request);
    }
}
