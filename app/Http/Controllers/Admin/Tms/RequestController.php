<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\TransportRequestService;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = TmsTransportRequest::query()
            ->with(['employee', 'vehicle', 'driver.employee', 'factory', 'destination'])
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

        $drivers = TmsDriver::with(['employee', 'defaultVehicle'])
            ->where('status', 'active')
            ->when($request->user()->factory_id, fn ($q, $fid) => $q->where('factory_id', $fid))
            ->orderBy('id')
            ->get();

        $vehicles = TmsVehicle::query()
            ->when($request->user()->factory_id, fn ($q, $fid) => $q->where('factory_id', $fid))
            ->orderBy('name')
            ->get();

        return view('admin.tms.requests.index', [
            'requests'  => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'drivers'   => $drivers,
            'vehicles'  => $vehicles,
            'statuses'  => config('tms.request_statuses'),
            'filters'   => $request->only(['status', 'factory_id', 'destination', 'pickup_date']),
        ]);
    }

    public function show(Request $request, TmsTransportRequest $transportRequest)
    {
        $this->authorizeFactoryAccess($request, $transportRequest->factory_id);

        $transportRequest->load([
            'employee', 'destination', 'vehicle', 'driver.employee', 'driver.defaultVehicle',
            'tripLog.transportRequests.employee', 'histories.changedByUser', 'histories.changedByEmployee',
            'approvedByUser',
        ]);

        $drivers = TmsDriver::with(['employee', 'defaultVehicle'])
            ->where('factory_id', $transportRequest->factory_id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        $vehicles = TmsVehicle::where('factory_id', $transportRequest->factory_id)
            ->orderBy('name')
            ->get();

        return view('admin.tms.requests.show', [
            'transportRequest' => $transportRequest,
            'drivers'          => $drivers,
            'vehicles'         => $vehicles,
            'statuses'         => config('tms.request_statuses'),
        ]);
    }

    public function merge(Request $request, TransportRequestService $service)
    {
        $validated = $request->validate([
            'request_ids'   => ['required', 'array', 'min:1'],
            'request_ids.*' => ['integer', 'exists:tms_transport_requests,id'],
            'driver_id'     => ['required', 'exists:tms_drivers,id'],
            'vehicle_id'    => ['nullable', 'exists:tms_vehicles,id'],
        ]);

        $first = TmsTransportRequest::findOrFail($validated['request_ids'][0]);
        $this->authorizeFactoryAccess($request, $first->factory_id);

        $trip = $service->mergeAndApprove(
            $validated['request_ids'],
            $request->user(),
            (int) $validated['driver_id'],
            isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : null,
        );

        $count = count($validated['request_ids']);

        return redirect()
            ->route('admin.tms.trips.show', $trip)
            ->with('success', "{$count} request(s) merged and assigned to trip #{$trip->id}.");
    }

    public function approve(Request $request, TmsTransportRequest $transportRequest, TransportRequestService $service)
    {
        $this->authorizeFactoryAccess($request, $transportRequest->factory_id);

        $validated = $request->validate([
            'driver_id'  => ['required', 'exists:tms_drivers,id'],
            'vehicle_id' => ['nullable', 'exists:tms_vehicles,id'],
        ]);

        $trip = $service->approve(
            $transportRequest,
            $request->user(),
            isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : null,
            (int) $validated['driver_id'],
        );

        return redirect()
            ->route('admin.tms.trips.show', $trip)
            ->with('success', 'Request approved and assigned to trip #' . $trip->id . '.');
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
}
