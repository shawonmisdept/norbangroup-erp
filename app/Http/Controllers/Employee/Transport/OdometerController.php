<?php

namespace App\Http\Controllers\Employee\Transport;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesPortalEmployee;
use App\Models\Tms\TmsDailyOdometerLog;
use App\Services\Tms\DailyOdometerService;
use App\Services\Tms\TripService;
use Illuminate\Http\Request;

class OdometerController extends Controller
{
    use ResolvesPortalEmployee;

    public function __construct(
        private DailyOdometerService $odometerService,
        private TripService $tripService,
    ) {}

    public function index(Request $request)
    {
        $employee = $this->portalEmployee($request);
        $driver = $this->tripService->driverForEmployee($employee);

        if (! $driver) {
            abort(403, 'You are not registered as a driver.');
        }

        $vehicle = $this->odometerService->driverVehicleOrFail($driver);

        $logs = TmsDailyOdometerLog::query()
            ->where('vehicle_id', $vehicle->id)
            ->latest('log_date')
            ->latest('id')
            ->paginate(15);

        $todayLog = TmsDailyOdometerLog::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereDate('log_date', today())
            ->first();

        return view('employee.transport.odometer', [
            'logs'     => $logs,
            'vehicle'  => $vehicle,
            'driver'   => $driver,
            'todayLog' => $todayLog,
        ]);
    }

    public function storeMorning(Request $request)
    {
        $employee = $this->portalEmployee($request);
        $driver = $this->tripService->driverForEmployee($employee);
        $vehicle = $this->odometerService->driverVehicleOrFail($driver);

        $validated = $request->validate([
            'morning_km' => ['required', 'numeric', 'min:0'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        $this->odometerService->storeMorning([
            'factory_id' => $vehicle->factory_id,
            'vehicle_id' => $vehicle->id,
            'log_date'   => today()->toDateString(),
            'morning_km' => $validated['morning_km'],
            'notes'      => $validated['notes'] ?? null,
        ], employee: $employee);

        return redirect()
            ->route('employee.transport.odometer')
            ->with('success', 'Morning KM saved.');
    }

    public function storeEvening(Request $request, TmsDailyOdometerLog $odometer)
    {
        $employee = $this->portalEmployee($request);
        $driver = $this->tripService->driverForEmployee($employee);

        $this->odometerService->assertDriverVehicle($driver, $odometer);
        $this->odometerService->ensureCanRecordEvening($odometer);

        $validated = $request->validate([
            'evening_km' => ['required', 'numeric', 'min:0'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        $this->odometerService->storeEvening(
            $odometer,
            (float) $validated['evening_km'],
            $validated['notes'] ?? null,
            employee: $employee,
        );

        return redirect()
            ->route('employee.transport.odometer')
            ->with('success', 'Evening KM saved.');
    }
}
