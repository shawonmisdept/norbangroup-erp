<?php

namespace App\Services\Tms;

use App\Models\Hrm\Employee;
use App\Models\Tms\TmsDailyOdometerLog;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsRentalVehicleCharge;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DailyOdometerService
{
    public function __construct(
        private DailyRentalBillingService $rentalBilling,
    ) {}

    public function storeMorning(
        array $validated,
        ?User $user = null,
        ?Employee $employee = null,
        ?TmsRentalDriver $rentalDriver = null,
    ): TmsDailyOdometerLog {
        $existing = TmsDailyOdometerLog::query()
            ->where('vehicle_id', $validated['vehicle_id'])
            ->whereDate('log_date', $validated['log_date'])
            ->first();

        if ($existing?->hasMorning()) {
            throw ValidationException::withMessages([
                'morning_km' => 'Morning KM is already recorded for this vehicle and date.',
            ]);
        }

        $now = now();

        $log = TmsDailyOdometerLog::updateOrCreate(
            ['vehicle_id' => $validated['vehicle_id'], 'log_date' => $validated['log_date']],
            [
                'factory_id'                       => $validated['factory_id'],
                'morning_km'                       => $validated['morning_km'],
                'morning_recorded_at'              => $now,
                'morning_entered_by'               => $user?->id,
                'morning_entered_by_employee'      => $employee?->id,
                'morning_entered_by_rental_driver' => $rentalDriver?->id,
                'notes'                            => $validated['notes'] ?? $existing?->notes,
            ]
        );

        return $log->fresh(['vehicle']);
    }

    public function storeEvening(
        TmsDailyOdometerLog $log,
        float $eveningKm,
        ?string $notes = null,
        ?User $user = null,
        ?Employee $employee = null,
        ?TmsRentalDriver $rentalDriver = null,
    ): TmsDailyOdometerLog {
        $this->ensureCanRecordEvening($log);

        if ($eveningKm < (float) $log->morning_km) {
            throw ValidationException::withMessages([
                'evening_km' => 'Evening KM must be greater than or equal to morning KM (' . number_format((float) $log->morning_km, 2) . ').',
            ]);
        }

        $log->update([
            'evening_km'                       => $eveningKm,
            'evening_recorded_at'              => now(),
            'evening_entered_by'               => $user?->id,
            'evening_entered_by_employee'      => $employee?->id,
            'evening_entered_by_rental_driver' => $rentalDriver?->id,
            'notes'                            => $notes ?? $log->notes,
        ]);

        $log = $log->fresh(['vehicle']);

        $this->syncVehicleOdometer($log);
        $this->rentalBilling->syncFromOdometerLog($log);

        return $log->fresh(['vehicle']);
    }

    public function ensureCanRecordEvening(TmsDailyOdometerLog $log): void
    {
        if (! $log->hasMorning()) {
            abort(404, 'Morning KM is not recorded yet.');
        }

        if ($log->hasEvening()) {
            abort(403, 'Evening KM is already recorded.');
        }
    }

    public function assertDriverVehicle(TmsDriver $driver, TmsDailyOdometerLog $log): void
    {
        if ((int) $driver->default_vehicle_id !== (int) $log->vehicle_id) {
            abort(403, 'This log does not belong to your assigned vehicle.');
        }
    }

    public function assertRentalDriverVehicle(TmsRentalDriver $driver, TmsDailyOdometerLog $log): void
    {
        if ((int) $driver->default_vehicle_id !== (int) $log->vehicle_id) {
            abort(403, 'This log does not belong to your assigned vehicle.');
        }
    }

    public function driverVehicleOrFail(TmsDriver $driver): TmsVehicle
    {
        $vehicle = $driver->defaultVehicle;

        if (! $vehicle) {
            abort(403, 'No default vehicle assigned to your driver profile.');
        }

        return $vehicle;
    }

    public function rentalDriverVehicleOrFail(TmsRentalDriver $driver): TmsVehicle
    {
        $vehicle = $driver->defaultVehicle;

        if (! $vehicle) {
            abort(403, 'No default vehicle assigned to your driver profile.');
        }

        return $vehicle;
    }

    public function syncVehicleOdometer(TmsDailyOdometerLog $log): void
    {
        if ($log->evening_km !== null) {
            TmsVehicle::whereKey($log->vehicle_id)->update(['last_odometer_km' => $log->evening_km]);
        }
    }

    public function deleteLog(TmsDailyOdometerLog $log): void
    {
        $charge = TmsRentalVehicleCharge::where('odometer_log_id', $log->id)->first();

        if ($charge?->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'odometer' => 'Cannot delete — a paid rental charge is linked to this log.',
            ]);
        }

        $vehicleId = (int) $log->vehicle_id;

        if ($charge) {
            $charge->delete();
        }

        $log->delete();

        $latest = TmsDailyOdometerLog::query()
            ->where('vehicle_id', $vehicleId)
            ->whereNotNull('evening_km')
            ->orderByDesc('log_date')
            ->orderByDesc('id')
            ->first();

        TmsVehicle::whereKey($vehicleId)->update([
            'last_odometer_km' => $latest?->evening_km ?? 0,
        ]);
    }
}
