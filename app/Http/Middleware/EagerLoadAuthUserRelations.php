<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EagerLoadAuthUserRelations
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $user->loadMissing(['role', 'factory']);
        }

        return $next($request);
    }
}
