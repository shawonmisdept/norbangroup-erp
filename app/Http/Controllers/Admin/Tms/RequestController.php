<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\TransportRequestService;
use App\Services\Tms\VehiclePaperService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private VehiclePaperService $paperService,
    ) {}

    public function index(Request $request)
    {
        $query = TmsTransportRequest::query()
            ->with(['employee', 'vehicle', 'driver.employee', 'rentalDriver', 'factory', 'destination'])
            ->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }
        if ($request->filled('destination')) {
            $term = $request->destination;
            $query->where(function ($q) use ($term) {
                $q->where('destination_custom', 'like', "%{$term}%")
                    ->orWhereHas('destination', fn ($d) => $d->where('name', 'like', "%{$term}%"));
            });
        }
        if ($request->filled('pickup_date')) {
            $query->whereDate('pickup_at', $request->pickup_date);
        }

        $factoryId = $request->user()->factory_id;

        $drivers = TmsDriver::with(['employee', 'vehicles', 'defaultVehicle'])
            ->where('status', 'active')
            ->when($factoryId, fn ($q, $fid) => $q->where('factory_id', $fid))
            ->orderBy('id')
            ->get();

        $rentalDrivers = TmsRentalDriver::with('defaultVehicle')
            ->where('status', 'active')
            ->when($factoryId, fn ($q, $fid) => $q->where('factory_id', $fid))
            ->orderBy('name')
            ->get();

        $vehicles = TmsVehicle::query()
            ->when($factoryId, fn ($q, $fid) => $q->where('factory_id', $fid))
            ->orderBy('name')
            ->get();

        return view('admin.tms.requests.index', [
            'requests'            => $query->paginate(25)->withQueryString(),
            'factories'           => $this->factoryOptions($request),
            'drivers'             => $drivers,
            'rentalDrivers'       => $rentalDrivers,
            'vehicles'            => $vehicles,
            'vehiclePaperWarnings'=> $this->vehiclePaperWarningsMap($vehicles),
            'statuses'            => config('tms.request_statuses'),
            'filters'             => $request->only(['status', 'factory_id', 'destination', 'pickup_date']),
        ]);
    }

    public function show(Request $request, TmsTransportRequest $transportRequest)
    {
        $this->authorizeFactoryAccess($request, $transportRequest->factory_id);

        $transportRequest->load([
            'employee', 'destination', 'vehicle', 'driver.employee', 'driver.defaultVehicle', 'rentalDriver',
            'tripLog.transportRequests.employee', 'histories.changedByUser', 'histories.changedByEmployee',
            'approvedByUser',
        ]);

        $drivers = TmsDriver::with(['employee', 'vehicles', 'defaultVehicle'])
            ->where('factory_id', $transportRequest->factory_id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        $rentalDrivers = TmsRentalDriver::with('defaultVehicle')
            ->where('factory_id', $transportRequest->factory_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $vehicles = TmsVehicle::where('factory_id', $transportRequest->factory_id)
            ->orderBy('name')
            ->get();

        return view('admin.tms.requests.show', [
            'transportRequest'     => $transportRequest,
            'drivers'              => $drivers,
            'rentalDrivers'        => $rentalDrivers,
            'vehicles'             => $vehicles,
            'vehiclePaperWarnings' => $this->vehiclePaperWarningsMap($vehicles),
            'statuses'             => config('tms.request_statuses'),
        ]);
    }

    public function merge(Request $request, TransportRequestService $service)
    {
        $validated = $this->validateAssignment($request);

        $first = TmsTransportRequest::findOrFail($validated['request_ids'][0]);
        $this->authorizeFactoryAccess($request, $first->factory_id);

        $trip = $service->mergeAndApprove(
            $validated['request_ids'],
            $request->user(),
            $validated['driver_type'],
            $validated['driver_id'] ?? null,
            $validated['rental_driver_id'] ?? null,
            isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : null,
        );

        $count = count($validated['request_ids']);

        return $this->redirectAfterTripAssignment(
            $request,
            $trip,
            $first,
            "{$count} request(s) merged and assigned to trip #{$trip->id}.",
        );
    }

    public function approve(Request $request, TmsTransportRequest $transportRequest, TransportRequestService $service)
    {
        $this->authorizeFactoryAccess($request, $transportRequest->factory_id);

        $validated = $this->validateAssignment($request);

        $trip = $service->approve(
            $transportRequest,
            $request->user(),
            isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : null,
            $validated['driver_type'],
            $validated['driver_id'] ?? null,
            $validated['rental_driver_id'] ?? null,
        );

        return $this->redirectAfterTripAssignment($request, $trip, $transportRequest, 'Request approved and assigned to trip #' . $trip->id . '.');
    }

    public function reject(Request $request, TmsTransportRequest $transportRequest, TransportRequestService $service)
    {
        $this->authorizeFactoryAccess($request, $transportRequest->factory_id);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->reject($transportRequest, $request->user(), $validated['rejection_reason']);

        return redirect()->route('admin.tms.requests.show', $transportRequest)->with('success', 'Request rejected.');
    }

    public function cancel(Request $request, TmsTransportRequest $transportRequest, TransportRequestService $service)
    {
        $this->authorizeFactoryAccess($request, $transportRequest->factory_id);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->adminCancelApproved($transportRequest, $request->user(), $validated['reason'] ?? null);

        return redirect()->route('admin.tms.requests.show', $transportRequest)->with('success', 'Request cancelled.');
    }

    public function reassign(Request $request, TmsTransportRequest $transportRequest, TransportRequestService $service)
    {
        $this->authorizeFactoryAccess($request, $transportRequest->factory_id);

        if (! $transportRequest->trip_log_id) {
            return back()->withErrors(['trip' => 'Request is not assigned to a trip.']);
        }

        $validated = $this->validateAssignment($request);

        $trip = $service->reassignTrip(
            $transportRequest->tripLog()->firstOrFail(),
            $request->user(),
            $validated['driver_type'],
            $validated['driver_id'] ?? null,
            $validated['rental_driver_id'] ?? null,
            isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : null,
        );

        return $this->redirectAfterTripAssignment(
            $request,
            $trip,
            $transportRequest,
            'Driver and vehicle reassigned for trip #' . $trip->id . '.',
        );
    }

    private function redirectAfterTripAssignment(Request $request, TmsTripLog $trip, TmsTransportRequest $transportRequest, string $message): RedirectResponse
    {
        if ($request->user()->hasPermission('tms.trips.view')) {
            return redirect()
                ->route('admin.tms.trips.show', $trip)
                ->with('success', $message);
        }

        return redirect()
            ->route('admin.tms.requests.show', $transportRequest)
            ->with('success', $message);
    }

    /** @return array<string, mixed> */
    private function validateAssignment(Request $request): array
    {
        return $request->validate([
            'request_ids'       => ['sometimes', 'array', 'min:1'],
            'request_ids.*'     => ['integer', 'exists:tms_transport_requests,id'],
            'driver_type'       => ['required', 'in:company,rental'],
            'driver_id'         => ['nullable', 'required_if:driver_type,company', 'exists:tms_drivers,id'],
            'rental_driver_id'  => ['nullable', 'required_if:driver_type,rental', 'exists:tms_rental_drivers,id'],
            'vehicle_id'        => ['nullable', 'exists:tms_vehicles,id'],
        ]);
    }

    /** @return array<int, array<int, string>> */
    private function vehiclePaperWarningsMap($vehicles): array
    {
        $map = [];

        foreach ($vehicles as $vehicle) {
            $warnings = $this->paperService->warningMessagesForVehicle($vehicle);
            if ($warnings !== []) {
                $map[$vehicle->id] = $warnings;
            }
        }

        return $map;
    }
}
