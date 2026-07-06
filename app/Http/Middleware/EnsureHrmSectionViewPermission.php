<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHrmSectionViewPermission
{
    public function handle(Request $request, Closure $next, string $section): Response
    {
        $user = $request->user();

        $method = match ($section) {
            'employees'  => 'hasAnyEmployeeViewPermission',
            'leave'      => 'hasAnyLeaveViewPermission',
            'attendance' => 'hasAnyAttendanceViewPermission',
            'salary'     => 'hasAnySalaryViewPermission',
            'compliance' => 'hasAnyComplianceViewPermission',
            'finance'    => 'hasAnyFinanceViewPermission',
            'rmg'        => 'hasAnyRmgViewPermission',
            default      => null,
        };

        if (! $method || ! $user || ! $user->{$method}()) {
            abort(403, 'You do not have permission to access this module.');
        }

        return $next($request);
    }
}
