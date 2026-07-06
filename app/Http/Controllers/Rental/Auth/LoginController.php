<?php

namespace App\Http\Controllers\Rental\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsRentalDriverPortalUser;
use App\Support\PortalLoginRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return response()
            ->view('rental.auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'mobile'   => ['required', 'string', 'max:32'],
            'password' => ['required', 'string'],
        ]);

        $driver = TmsRentalDriver::query()
            ->where('mobile', $credentials['mobile'])
            ->first();

        $portalUser = $driver
            ? TmsRentalDriverPortalUser::query()->where('rental_driver_id', $driver->id)->first()
            : null;

        if (! $portalUser || ! $portalUser->canLogin()) {
            throw ValidationException::withMessages([
                'mobile' => 'Portal access is not available for this mobile number.',
            ]);
        }

        if (! Auth::guard('rental_driver')->attempt(
            ['id' => $portalUser->id, 'password' => $credentials['password']],
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'mobile' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        $portalUser->update(['last_login_at' => now()]);

        return PortalLoginRedirect::afterLogin('/rental', route('rental.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('rental_driver')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('rental.login');
    }
}
