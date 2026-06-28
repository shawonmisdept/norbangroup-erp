<?php

namespace App\Http\Controllers\Employee\Transport;

use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDestination;
use App\Models\Tms\TmsTransportRequest;
use App\Services\Tms\TransportRequestService;
use App\Services\Tms\TripService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user('employee')->employee;

        $requests = TmsTransportRequest::query()
            ->with(['destination', 'vehicle', 'driver.employee', 'tripLog'])
            ->where('employee_id', $employee->id)
            ->latest('id')
            ->paginate(15);

        $isDriver = app(TripService::class)->driverForEmployee($employee) !== null;

        return view('employee.transport.index', compact('requests', 'employee', 'isDriver'));
    }

    public function create(Request $request)
    {
        $employee = $request->user('employee')->employee;

        $destinations = TmsDestination::where('factory_id', $employee->factory_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('employee.transport.create', compact('employee', 'destinations'));
    }

    public function store(Request $request, TransportRequestService $service)
    {
        $employee = $request->user('employee')->employee;
        $grace = (int) config('tms.pickup_grace_minutes', 0);

        $validated = $request->validate([
            'pickup_location'    => ['required', 'string', 'max:500'],
            'destination_id'     => ['nullable', 'exists:tms_destinations,id'],
            'destination_custom' => ['nullable', 'string', 'max:500'],
            'pickup_at'          => ['required', 'date', 'after_or_equal:' . now()->subMinutes($grace)->toDateTimeString()],
            'purpose'            => ['required', 'string', 'max:1000'],
            'passenger_count'    => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        if (empty($validated['destination_id']) && empty($validated['destination_custom'])) {
            return back()->withErrors(['destination_custom' => 'Select a destination or enter a custom destination.'])->withInput();
        }

        if (! empty($validated['destination_id'])) {
            $exists = TmsDestination::where('id', $validated['destination_id'])
                ->where('factory_id', $employee->factory_id)
                ->exists();
            if (! $exists) {
                abort(403);
            }
        }

        $validated['pickup_at'] = Carbon::parse($validated['pickup_at'], config('app.timezone'));

        $service->submit($employee, $validated);

        return redirect()->route('employee.transport.index')->with('success', 'Transport request submitted.');
    }

    public function show(Request $request, TmsTransportRequest $transportRequest)
    {
        $employee = $request->user('employee')->employee;

        if ($transportRequest->employee_id !== $employee->id) {
            abort(403);
        }

        $transportRequest->load(['destination', 'vehicle', 'driver.employee', 'tripLog', 'histories']);

        return view('employee.transport.show', compact('transportRequest', 'employee'));
    }

    public function cancel(Request $request, TmsTransportRequest $transportRequest, TransportRequestService $service)
    {
        $employee = $request->user('employee')->employee;
        $service->cancel($transportRequest, $employee);

        return redirect()->route('employee.transport.index')->with('success', 'Request cancelled.');
    }
}
