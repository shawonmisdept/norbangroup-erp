<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsRentalVehicleCharge;
use App\Models\Tms\TmsTripLog;
use App\Services\Tms\TripService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = TmsTripLog::query()
            ->with(['transportRequests.employee', 'transportRequest.employee', 'vehicle', 'driver.employee', 'factory'])
            ->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('trip_status')) {
            $query->where('trip_status', $request->trip_status);
        }

        return view('admin.tms.trips.index', [
            'trips'    => $query->paginate(25)->withQueryString(),
            'statuses' => config('tms.trip_statuses'),
            'filters'  => $request->only(['trip_status']),
        ]);
    }

    public function show(Request $request, TmsTripLog $trip)
    {
        $this->authorizeFactoryAccess($request, $trip->factory_id);

        $trip->load([
            'transportRequests.employee', 'transportRequest.employee', 'vehicle.rentalVendor',
            'driver.employee', 'rentalDriver', 'overtimePayment', 'rentalVehicleCharge.rentalVendor', 'fuelLogs',
        ]);

        return view('admin.tms.trips.show', ['trip' => $trip]);
    }

    public function start(Request $request, TmsTripLog $trip, TripService $tripService)
    {
        $this->authorizeFactoryAccess($request, $trip->factory_id);

        $validated = $request->validate([
            'start_km' => ['nullable', 'numeric', 'min:0'],
        ]);

        $startKm = isset($validated['start_km']) ? (float) $validated['start_km'] : null;
        $tripService->start($trip, startKm: $startKm);

        return redirect()->route('admin.tms.trips.show', $trip)->with('success', 'Trip started.');
    }

    public function end(Request $request, TmsTripLog $trip, TripService $tripService)
    {
        $this->authorizeFactoryAccess($request, $trip->factory_id);

        $validated = $request->validate([
            'end_km' => ['nullable', 'numeric', 'min:0'],
        ]);

        $endKm = isset($validated['end_km']) ? (float) $validated['end_km'] : null;
        $tripService->end($trip, endKm: $endKm);

        return redirect()->route('admin.tms.trips.show', $trip)->with('success', 'Trip completed.');
    }

    public function markOtPaid(Request $request, TmsTripLog $trip)
    {
        $this->authorizeFactoryAccess($request, $trip->factory_id);

        $payment = TmsDriverOvertimePayment::where('trip_log_id', $trip->id)->firstOrFail();

        $payment->update([
            'payment_status' => 'paid',
            'paid_at'        => now(),
            'paid_by'        => $request->user()->id,
        ]);

        return redirect()->route('admin.tms.trips.show', $trip)->with('success', 'OT marked as paid.');
    }

    public function markRentalChargePaid(Request $request, TmsTripLog $trip)
    {
        $this->authorizeFactoryAccess($request, $trip->factory_id);

        $charge = TmsRentalVehicleCharge::where('trip_log_id', $trip->id)->firstOrFail();

        $charge->update([
            'payment_status' => 'paid',
            'paid_at'        => now(),
            'paid_by'        => $request->user()->id,
        ]);

        return redirect()->route('admin.tms.trips.show', $trip)->with('success', 'Rental charge marked as paid.');
    }
}
