<?php

namespace App\Http\Controllers\Employee\Transport;

use App\Http\Controllers\Controller;
use App\Models\Tms\TmsTripLog;
use App\Services\Tms\TripService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request, TripService $tripService)
    {
        $employee = $request->user('employee')->employee;
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
        $employee = $request->user('employee')->employee;
        $tripService->start($trip, $employee);

        return redirect()->route('employee.transport.trips')->with('success', 'Trip started.');
    }

    public function end(Request $request, TmsTripLog $trip, TripService $tripService)
    {
        $employee = $request->user('employee')->employee;
        $tripService->end($trip, $employee);

        return redirect()->route('employee.transport.trips')->with('success', 'Trip completed.');
    }
}
