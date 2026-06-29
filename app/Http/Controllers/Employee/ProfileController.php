<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesPortalEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    use ResolvesPortalEmployee;

    public function show(Request $request)
    {
        $portalUser = Auth::guard('employee')->user();
        $employee = $this->portalEmployee($request)->load([
            'factory', 'department', 'designation', 'workerCategory', 'employmentType',
            'building', 'floor', 'line', 'shift',
        ]);

        return view('employee.profile', compact('employee', 'portalUser'));
    }
}
