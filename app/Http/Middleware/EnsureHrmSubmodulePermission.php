<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHrmSubmodulePermission
{
    public function handle(Request $request, Closure $next, string $section, string $submodule, string $action = 'manage'): Response
    {
        $user = $request->user();

        $method = match ($section) {
            'employees'  => $action === 'view' ? 'canViewEmployeeSubmodule' : 'canManageEmployeeSubmodule',
            'leave'      => $action === 'view' ? 'canViewLeaveSubmodule' : 'canManageLeaveSubmodule',
            'attendance' => $action === 'view' ? 'canViewAttendanceSubmodule' : 'canManageAttendanceSubmodule',
            'salary'     => $action === 'view' ? 'canViewSalarySubmodule' : 'canManageSalarySubmodule',
            'compliance' => $action === 'view' ? 'canViewComplianceSubmodule' : 'canManageComplianceSubmodule',
            'finance'    => $action === 'view' ? 'canViewFinanceSubmodule' : 'canManageFinanceSubmodule',
            'rmg'        => $action === 'view' ? 'canViewRmgSubmodule' : 'canManageRmgSubmodule',
            default      => null,
        };

        if (! $method || ! $user || ! $user->{$method}($submodule)) {
            abort(403, 'You do not have permission to access this module.');
        }

        return $next($request);
    }
}
