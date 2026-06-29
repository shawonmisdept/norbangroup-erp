<?php

namespace App\Http\Controllers\Rental;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Rental\Concerns\ResolvesPortalRentalDriver;
use App\Models\Tms\TmsDailyOdometerLog;
use App\Services\Tms\DailyOdometerService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OdometerController extends Controller
{
    use ResolvesPortalRentalDriver;

    public function __construct(
        private DailyOdometerService $odometerService,
    ) {}

    public function index(Request $request)
    {
        $driver = $this->portalRentalDriver($request);
        $vehicle = $this->odometerService->rentalDriverVehicleOrFail($driver);

        $query = TmsDailyOdometerLog::query()
            ->where('vehicle_id', $vehicle->id)
            ->latest('log_date')
            ->latest('id');

        if ($request->filled('from')) {
            $query->whereDate('log_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('log_date', '<=', $request->to);
        }

        return view('rental.odometer.index', [
            'logs'    => $query->paginate(15)->withQueryString(),
            'vehicle' => $vehicle,
            'driver'  => $driver,
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function createMorning(Request $request)
    {
        $driver = $this->portalRentalDriver($request);
        $vehicle = $this->odometerService->rentalDriverVehicleOrFail($driver);

        return view('rental.odometer.morning-form', [
            'driver'  => $driver,
            'vehicle' => $vehicle,
            'log'     => new TmsDailyOdometerLog(['log_date' => now()->toDateString()]),
        ]);
    }

    public function storeMorning(Request $request)
    {
        $driver = $this->portalRentalDriver($request);
        $vehicle = $this->odometerService->rentalDriverVehicleOrFail($driver);

        $validated = $request->validate([
            'log_date'   => ['required', 'date'],
            'morning_km' => ['required', 'numeric', 'min:0'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $log = $this->odometerService->storeMorning([
                'factory_id' => $vehicle->factory_id,
                'vehicle_id' => $vehicle->id,
                'log_date'   => $validated['log_date'],
                'morning_km' => $validated['morning_km'],
                'notes'      => $validated['notes'] ?? null,
            ], rentalDriver: $driver);
        } catch (ValidationException) {
            throw ValidationException::withMessages([
                'morning_km' => 'Morning KM is already recorded for this vehicle and date.',
            ]);
        }

        return redirect()
            ->route('rental.odometer')
            ->with('success', 'Morning KM saved for ' . $log->vehicle?->displayLabel() . '.');
    }

    public function createEvening(Request $request, TmsDailyOdometerLog $odometer)
    {
        $driver = $this->portalRentalDriver($request);
        $this->odometerService->assertRentalDriverVehicle($driver, $odometer);
        $this->odometerService->ensureCanRecordEvening($odometer);

        return view('rental.odometer.evening-form', [
            'log'     => $odometer->load('vehicle'),
            'driver'  => $driver,
            'vehicle' => $odometer->vehicle,
        ]);
    }

    public function storeEvening(Request $request, TmsDailyOdometerLog $odometer)
    {
        $driver = $this->portalRentalDriver($request);
        $this->odometerService->assertRentalDriverVehicle($driver, $odometer);
        $this->odometerService->ensureCanRecordEvening($odometer);

        $validated = $request->validate([
            'evening_km' => ['required', 'numeric', 'min:0'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        $this->odometerService->storeEvening(
            $odometer,
            (float) $validated['evening_km'],
            $validated['notes'] ?? null,
            rentalDriver: $driver,
        );

        return redirect()
            ->route('rental.odometer')
            ->with('success', 'Evening KM saved for ' . $odometer->vehicle?->displayLabel() . '.');
    }
}
