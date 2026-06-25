<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $portalUser = Auth::guard('employee')->user();
        $employee = $portalUser->employee()->with([
            'factory', 'department', 'designation', 'workerCategory', 'employmentType',
            'building', 'floor', 'line', 'shift',
        ])->first();

        return view('employee.profile', compact('employee', 'portalUser'));
    }
}
