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
            'building', 'floor', 'line', 'shift', 'salaryStructure.salaryGrade',
            'serviceHistories' => fn ($q) => $q->limit(20),
            'employmentHistories',
        ]);

        $letters = $employee->portalVisibleLetters()->limit(10)->get();

        return view('employee.profile', compact('employee', 'portalUser', 'letters'));
    }
}
