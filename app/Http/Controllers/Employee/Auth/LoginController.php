<?php

namespace App\Http\Controllers\Employee\Auth;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('employee.auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'employee_code' => ['required', 'string', 'max:30'],
            'password'      => ['required', 'string'],
        ]);

        $employee = Employee::query()
            ->where('employee_code', $credentials['employee_code'])
            ->first();

        $portalUser = $employee
            ? EmployeePortalUser::query()->where('employee_id', $employee->id)->first()
            : null;

        if (! $portalUser || ! $portalUser->canLogin()) {
            throw ValidationException::withMessages([
                'employee_code' => 'Portal access is not available for this Employee ID.',
            ]);
        }

        if (! Auth::guard('employee')->attempt(
            ['id' => $portalUser->id, 'password' => $credentials['password']],
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'employee_code' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        $portalUser->update(['last_login_at' => now()]);

        return redirect()->intended(route('employee.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('employee')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }
}
