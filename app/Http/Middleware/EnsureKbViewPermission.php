<?php

namespace App\Http\Middleware;

use App\Services\KbAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKbViewPermission
{
    public function __construct(
        private KbAccessService $access,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->access->canViewKb($request->user())) {
            abort(403, 'You do not have permission to view the knowledge base.');
        }

        return $next($request);
    }
}
