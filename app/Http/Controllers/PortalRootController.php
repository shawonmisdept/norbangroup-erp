<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PortalRootController extends Controller
{
    public function employee(): RedirectResponse
    {
        if (Auth::guard('employee')->check()) {
            return redirect()->route('employee.dashboard');
        }

        return redirect()->route('employee.login');
    }

    public function rental(): RedirectResponse
    {
        if (Auth::guard('rental_driver')->check()) {
            return redirect()->route('rental.dashboard');
        }

        return redirect()->route('rental.login');
    }
}
