<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDailyOdometerLog;
use App\Models\Tms\TmsVehicle;
use Illuminate\Http\Request;

class OdometerController extends Controller
{
    use ScopesHrmFactory;

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
            ->when($request->user()->factory_id, fn ($q, $fid) => $q->where('factory_id', $fid))
            ->orderBy('name')
            ->get();

        return view('admin.tms.odometer.index', [
            'logs'      => $query->paginate(25)->withQueryString(),
            'vehicles'  => $vehicles,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id', 'from', 'to', 'vehicle_id']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.tms.odometer.form', [
            'log'       => new TmsDailyOdometerLog(['log_date' => now()->toDateString()]),
            'vehicles'  => $this->vehicleOptions($request),
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateLog($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $log = TmsDailyOdometerLog::updateOrCreate(
            ['vehicle_id' => $validated['vehicle_id'], 'log_date' => $validated['log_date']],
            $this->logPayload($request, $validated)
        );

        $this->syncVehicleOdometer($log);

        return redirect()->route('admin.tms.odometer.index')->with('success', 'Daily odometer log saved.');
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
        $validated = $this->validateLog($request, $odometer);
        $odometer->update($this->logPayload($request, $validated, $odometer));
        $this->syncVehicleOdometer($odometer->fresh());

        return redirect()->route('admin.tms.odometer.index')->with('success', 'Daily odometer log updated.');
    }

    private function validateLog(Request $request, ?TmsDailyOdometerLog $log = null): array
    {
        $data = $request->validate([
            'factory_id'  => ['required', 'exists:factories,id'],
            'vehicle_id'  => ['required', 'exists:tms_vehicles,id'],
            'log_date'    => ['required', 'date'],
            'morning_km'  => ['nullable', 'numeric', 'min:0'],
            'evening_km'  => ['nullable', 'numeric', 'min:0'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['morning_km'] !== null && $data['evening_km'] !== null && (float) $data['evening_km'] < (float) $data['morning_km']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'evening_km' => 'Evening KM must be greater than or equal to morning KM.',
            ]);
        }

        return $data;
    }

    private function logPayload(Request $request, array $validated, ?TmsDailyOdometerLog $existing = null): array
    {
        $payload = [
            'factory_id' => $validated['factory_id'],
            'vehicle_id' => $validated['vehicle_id'],
            'log_date'   => $validated['log_date'],
            'morning_km' => $validated['morning_km'],
            'evening_km' => $validated['evening_km'],
            'notes'      => $validated['notes'] ?? null,
        ];

        if ($request->filled('morning_km')) {
            $payload['morning_entered_by'] = $request->user()->id;
        } elseif ($existing) {
            $payload['morning_entered_by'] = $existing->morning_entered_by;
        }

        if ($request->filled('evening_km')) {
            $payload['evening_entered_by'] = $request->user()->id;
        } elseif ($existing) {
            $payload['evening_entered_by'] = $existing->evening_entered_by;
        }

        return $payload;
    }

    private function syncVehicleOdometer(TmsDailyOdometerLog $log): void
    {
        if ($log->evening_km !== null) {
            TmsVehicle::whereKey($log->vehicle_id)->update(['last_odometer_km' => $log->evening_km]);
        }
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
