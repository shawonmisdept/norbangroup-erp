<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDailyOdometerLog;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\DailyOdometerService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OdometerController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private DailyOdometerService $odometerService,
    ) {}

    public function index(Request $request)
    {
        $query = TmsDailyOdometerLog::query()
            ->with(['vehicle.defaultDrivers.employee', 'factory'])
            ->latest('log_date')
            ->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('log_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('log_date', '<=', $request->to);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $vehicles = TmsVehicle::query()
            ->when($request->user()->factory_id, fn ($q) => $q->where('factory_id', $request->user()->factory_id))
            ->orderBy('name')
            ->get();

        return view('admin.tms.odometer.index', [
            'logs'      => $query->paginate(25)->withQueryString(),
            'vehicles'  => $vehicles,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id', 'from', 'to', 'vehicle_id']),
        ]);
    }

    public function createMorning(Request $request)
    {
        return view('admin.tms.odometer.morning-form', [
            'log'       => new TmsDailyOdometerLog(['log_date' => now()->toDateString()]),
            'vehicles'  => $this->vehicleOptions($request),
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function storeMorning(Request $request)
    {
        $validated = $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'vehicle_id' => ['required', 'exists:tms_vehicles,id'],
            'log_date'   => ['required', 'date'],
            'morning_km' => ['required', 'numeric', 'min:0'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        try {
            $log = $this->odometerService->storeMorning($validated, user: $request->user());
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'morning_km' => 'Morning KM is already recorded for this vehicle and date. Use Edit to correct.',
            ]);
        }

        return redirect()
            ->route('admin.tms.odometer.index')
            ->with('success', 'Morning KM saved for ' . $log->vehicle?->displayLabel() . '.');
    }

    public function createEvening(Request $request, TmsDailyOdometerLog $odometer)
    {
        $this->authorizeFactoryAccess($request, $odometer->factory_id);
        $this->odometerService->ensureCanRecordEvening($odometer);

        return view('admin.tms.odometer.evening-form', [
            'log' => $odometer->load('vehicle'),
        ]);
    }

    public function storeEvening(Request $request, TmsDailyOdometerLog $odometer)
    {
        $this->authorizeFactoryAccess($request, $odometer->factory_id);

        $validated = $request->validate([
            'evening_km' => ['required', 'numeric', 'min:0'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        $this->odometerService->storeEvening(
            $odometer,
            (float) $validated['evening_km'],
            $validated['notes'] ?? null,
            user: $request->user(),
        );

        return redirect()
            ->route('admin.tms.odometer.index')
            ->with('success', 'Evening KM saved for ' . $odometer->vehicle?->displayLabel() . '.');
    }

    public function edit(Request $request, TmsDailyOdometerLog $odometer)
    {
        $this->authorizeFactoryAccess($request, $odometer->factory_id);

        return view('admin.tms.odometer.form', [
            'log'       => $odometer,
            'vehicles'  => $this->vehicleOptions($request, $odometer->factory_id),
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function update(Request $request, TmsDailyOdometerLog $odometer)
    {
        $this->authorizeFactoryAccess($request, $odometer->factory_id);

        $validated = $request->validate([
            'factory_id'  => ['required', 'exists:factories,id'],
            'vehicle_id'  => ['required', 'exists:tms_vehicles,id'],
            'log_date'    => ['required', 'date'],
            'morning_km'  => ['nullable', 'numeric', 'min:0'],
            'evening_km'  => ['nullable', 'numeric', 'min:0'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['morning_km'] !== null && $validated['evening_km'] !== null
            && (float) $validated['evening_km'] < (float) $validated['morning_km']) {
            throw ValidationException::withMessages([
                'evening_km' => 'Evening KM must be greater than or equal to morning KM.',
            ]);
        }

        $payload = [
            'factory_id' => $validated['factory_id'],
            'vehicle_id' => $validated['vehicle_id'],
            'log_date'   => $validated['log_date'],
            'morning_km' => $validated['morning_km'],
            'evening_km' => $validated['evening_km'],
            'notes'      => $validated['notes'] ?? null,
        ];

        if ($request->filled('morning_km') && (float) $validated['morning_km'] !== (float) $odometer->morning_km) {
            $payload['morning_entered_by'] = $request->user()->id;
            $payload['morning_recorded_at'] = now();
        }

        if ($request->filled('evening_km') && (float) $validated['evening_km'] !== (float) $odometer->evening_km) {
            $payload['evening_entered_by'] = $request->user()->id;
            $payload['evening_recorded_at'] = now();
        }

        $odometer->update($payload);
        $log = $odometer->fresh(['vehicle']);
        $this->odometerService->syncVehicleOdometer($log);

        if ($log->hasEvening()) {
            app(\App\Services\Tms\DailyRentalBillingService::class)->syncFromOdometerLog($log);
        }

        return redirect()
            ->route('admin.tms.odometer.index')
            ->with('success', 'Daily KM log updated.');
    }

    public function destroy(Request $request, TmsDailyOdometerLog $odometer)
    {
        $this->authorizeFactoryAccess($request, $odometer->factory_id);

        $this->odometerService->deleteLog($odometer);

        return redirect()
            ->route('admin.tms.odometer.index')
            ->with('success', 'Daily KM log deleted.');
    }

    private function vehicleOptions(Request $request, ?int $factoryId = null): \Illuminate\Support\Collection
    {
        $query = TmsVehicle::orderBy('name');

        $fid = $factoryId ?? $request->user()?->factory_id;
        if ($fid) {
            $query->where('factory_id', $fid);
        }

        return $query->get();
    }
}
