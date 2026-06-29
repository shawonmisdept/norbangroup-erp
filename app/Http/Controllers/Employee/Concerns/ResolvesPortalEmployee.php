<?php

namespace App\Http\Controllers\Employee\Concerns;

use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait ResolvesPortalEmployee
{
    protected function portalEmployee(Request $request): Employee
    {
        $portalUser = Auth::guard('employee')->user();

        if (! $portalUser instanceof EmployeePortalUser) {
            abort(403, 'Please sign in through the employee portal.');
        }

        $employee = $portalUser->relationLoaded('employee')
            ? $portalUser->employee
            : $portalUser->employee()->first();

        if (! $employee) {
            abort(403, 'Employee profile is not linked to this portal account.');
        }

        return $employee;
    }
}
