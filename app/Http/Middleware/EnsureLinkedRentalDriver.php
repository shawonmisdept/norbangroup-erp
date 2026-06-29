<?php

namespace App\Http\Middleware;

use App\Models\Tms\TmsRentalDriverPortalUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureLinkedRentalDriver
{
    public function handle(Request $request, Closure $next): Response
    {
        $portalUser = Auth::guard('rental_driver')->user();

        if (! $portalUser instanceof TmsRentalDriverPortalUser) {
            Auth::guard('rental_driver')->logout();

            return redirect()->route('rental.login');
        }

        if (! $portalUser->rentalDriver()->exists()) {
            Auth::guard('rental_driver')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('rental.login')
                ->withErrors([
                    'mobile' => 'Portal access is not linked to an active rental driver profile. Contact transport office.',
                ]);
        }

        return $next($request);
    }
}
