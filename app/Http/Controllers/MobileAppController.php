<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MobileAppController extends Controller
{
    public function landing(Request $request): View|RedirectResponse
    {
        return match ($request->query('portal')) {
            'employee' => redirect()->route('employee.login', ['source' => 'app']),
            'rental'   => redirect()->route('rental.login', ['source' => 'app']),
            default    => view('mobile.landing'),
        };
    }
}
