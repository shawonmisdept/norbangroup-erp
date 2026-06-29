<?php

namespace App\Http\Controllers\Employee\Transport;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesPortalEmployee;
use App\Models\Tms\TmsTripLog;
use App\Services\Tms\TripService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    use ResolvesPortalEmployee;

    public function index(Request $request, TripService $tripService)
    {
        $employee = $this->portalEmployee($request);
        $driver = $tripService->driverForEmployee($employee);

        if (! $driver) {
            abort(403, 'You are not registered as a driver.');
        }

        $trips = TmsTripLog::query()
            ->with(['transportRequests.employee', 'transportRequests.destination', 'transportRequest.employee', 'transportRequest.destination', 'vehicle'])
            ->where('driver_id', $driver->id)
            ->whereIn('trip_status', ['not_started', 'in_progress'])
            ->latest('id')
            ->get();

        return view('employee.transport.trips', compact('trips', 'employee', 'driver'));
    }

    public function start(Request $request, TmsTripLog $trip, TripService $tripService)
    {
        $employee = $this->portalEmployee($request);

        $validated = $request->validate([
            'start_km' => ['nullable', 'numeric', 'min:0'],
        ]);

        $startKm = isset($validated['start_km']) ? (float) $validated['start_km'] : null;
        $tripService->start($trip, $employee, $startKm);

        return redirect()->route('employee.transport.trips')->with('success', 'Trip started.');
    }

    public function end(Request $request, TmsTripLog $trip, TripService $tripService)
    {
        $employee = $this->portalEmployee($request);

        $validated = $request->validate([
            'end_km' => ['nullable', 'numeric', 'min:0'],
        ]);

        $endKm = isset($validated['end_km']) ? (float) $validated['end_km'] : null;
        $tripService->end($trip, $employee, $endKm);

        return redirect()->route('employee.transport.trips')->with('success', 'Trip completed.');
    }
}
