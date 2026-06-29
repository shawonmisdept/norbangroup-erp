<?php

namespace App\Http\Middleware;

use App\Models\Hrm\EmployeePortalUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureLinkedPortalEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        $portalUser = Auth::guard('employee')->user();

        if (! $portalUser instanceof EmployeePortalUser) {
            Auth::guard('employee')->logout();

            return redirect()->route('employee.login');
        }

        if (! $portalUser->employee()->exists()) {
            Auth::guard('employee')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('employee.login')
                ->withErrors([
                    'employee_code' => 'Portal access is not linked to an active employee profile. Contact HR.',
                ]);
        }

        return $next($request);
    }
}
