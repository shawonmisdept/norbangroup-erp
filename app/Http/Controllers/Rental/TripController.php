<?php

namespace App\Http\Controllers\Rental;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Rental\Concerns\ResolvesPortalRentalDriver;
use App\Models\Tms\TmsTripLog;
use App\Services\Tms\TripService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    use ResolvesPortalRentalDriver;

    public function index(Request $request)
    {
        $driver = $this->portalRentalDriver($request);

        $trips = TmsTripLog::query()
            ->with(['transportRequests.employee', 'transportRequests.destination', 'transportRequest.employee', 'transportRequest.destination', 'vehicle'])
            ->where('rental_driver_id', $driver->id)
            ->whereIn('trip_status', ['not_started', 'in_progress'])
            ->latest('id')
            ->get();

        return view('rental.trips', compact('trips', 'driver'));
    }

    public function start(Request $request, TmsTripLog $trip, TripService $tripService)
    {
        $driver = $this->portalRentalDriver($request);

        $tripService->start($trip, rentalDriver: $driver);

        return redirect()->route('rental.trips')->with('success', 'Trip started.');
    }

    public function end(Request $request, TmsTripLog $trip, TripService $tripService)
    {
        $driver = $this->portalRentalDriver($request);

        $tripService->end($trip, rentalDriver: $driver);

        return redirect()->route('rental.trips')->with('success', 'Trip completed.');
    }
}
