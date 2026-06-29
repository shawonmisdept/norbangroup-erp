<?php

namespace App\Http\Controllers\Rental;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Rental\Concerns\ResolvesPortalRentalDriver;
use App\Models\Tms\TmsTripLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ResolvesPortalRentalDriver;

    public function __invoke(Request $request)
    {
        $driver = $this->portalRentalDriver($request);
        $driver->load(['defaultVehicle', 'rentalVendor', 'factory']);

        $portalUser = Auth::guard('rental_driver')->user();

        $activeTrips = TmsTripLog::query()
            ->where('rental_driver_id', $driver->id)
            ->whereIn('trip_status', ['not_started', 'in_progress'])
            ->count();

        return view('rental.dashboard', [
            'driver'              => $driver,
            'activeTrips'         => $activeTrips,
            'unreadNotifications' => $portalUser->unreadNotifications()->count(),
        ]);
    }
}
