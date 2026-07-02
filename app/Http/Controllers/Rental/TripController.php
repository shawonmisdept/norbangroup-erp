<?php

namespace App\Http\Controllers\Rental;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Transport\Concerns\CapturesTripGpsInput;
use App\Http\Controllers\Rental\Concerns\ResolvesPortalRentalDriver;
use App\Models\Tms\TmsTripLog;
use App\Services\Tms\TripService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    use CapturesTripGpsInput;
    use ResolvesPortalRentalDriver;

    public function index(Request $request)
    {
        $driver = $this->portalRentalDriver($request);

        $tripRelations = ['transportRequests.employee', 'transportRequests.destination', 'transportRequest.employee', 'transportRequest.destination', 'vehicle'];

        $activeTrips = TmsTripLog::query()
            ->with($tripRelations)
            ->where('rental_driver_id', $driver->id)
            ->whereIn('trip_status', ['not_started', 'in_progress'])
            ->latest('id')
            ->get();

        $completedTrips = TmsTripLog::query()
            ->with($tripRelations)
            ->where('rental_driver_id', $driver->id)
            ->where('trip_status', 'completed')
            ->latest('duty_end_at')
            ->latest('id')
            ->limit(20)
            ->get();

        return view('rental.trips', compact('activeTrips', 'completedTrips', 'driver'));
    }

    public function start(Request $request, TmsTripLog $trip, TripService $tripService)
    {
        $driver = $this->portalRentalDriver($request);

        $tripService->start($trip, rentalDriver: $driver, gps: $this->tripGpsInput($request));

        return redirect()->route('rental.trips')->with('success', 'Trip started.');
    }

    public function end(Request $request, TmsTripLog $trip, TripService $tripService)
    {
        $driver = $this->portalRentalDriver($request);

        $tripService->end($trip, rentalDriver: $driver, gps: $this->tripGpsInput($request));

        return redirect()->route('rental.trips')->with('success', 'Trip completed.');
    }
}
