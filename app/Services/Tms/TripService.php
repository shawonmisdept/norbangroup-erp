<?php

namespace App\Services\Tms;

use App\Models\Hrm\Employee;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripService
{
    public function __construct(
        private DriverPayCalculator $driverPayCalculator,
        private TmsNotificationService $notifications,
    ) {}

    public function start(
        TmsTripLog $tripLog,
        ?Employee $employee = null,
        ?float $startKm = null,
        ?TmsRentalDriver $rentalDriver = null,
    ): TmsTripLog {
        if ($employee !== null) {
            $this->assertAssignedCompanyDriver($tripLog, $employee);
        }

        if ($rentalDriver !== null) {
            $this->assertAssignedRentalDriver($tripLog, $rentalDriver);
        }

        $requests = $this->linkedRequests($tripLog);

        if ($requests->isEmpty()) {
            throw ValidationException::withMessages(['trip' => 'No transport requests linked to this trip.']);
        }

        if ($requests->contains(fn ($r) => ! in_array($r->status, ['approved'], true))) {
            throw ValidationException::withMessages(['status' => 'Trip cannot be started — all passengers must be approved.']);
        }

        if ($tripLog->trip_status !== 'not_started') {
            throw ValidationException::withMessages(['trip' => 'Trip has already been started.']);
        }

        $tripLog->loadMissing('vehicle');

        if (! $tripLog->vehicle->isRental()) {
            $this->validateStartKm($tripLog->vehicle, $startKm);
        } else {
            $startKm = null;
        }

        return DB::transaction(function () use ($tripLog, $requests, $employee, $startKm) {
            $payload = [
                'duty_start_at' => now(),
                'trip_status'   => 'in_progress',
            ];

            if ($startKm !== null) {
                $payload['start_km'] = $startKm;
            }

            $tripLog->update($payload);

            foreach ($requests as $request) {
                $request->update(['status' => 'in_progress']);
                app(TransportRequestService::class)->recordStatusChange(
                    $request,
                    'approved',
                    'in_progress',
                    employeeId: $employee?->id
                );
                $this->notifications->tripStarted($request->fresh(['employee', 'vehicle', 'driver.employee', 'rentalDriver']));
            }

            $tripLog->vehicle->update(['status' => 'on_trip']);

            return $tripLog->fresh(['transportRequests.employee', 'vehicle', 'driver.employee', 'rentalDriver']);
        });
    }

    public function end(
        TmsTripLog $tripLog,
        ?Employee $employee = null,
        ?float $endKm = null,
        ?TmsRentalDriver $rentalDriver = null,
    ): TmsTripLog {
        if ($employee !== null) {
            $this->assertAssignedCompanyDriver($tripLog, $employee);
        }

        if ($rentalDriver !== null) {
            $this->assertAssignedRentalDriver($tripLog, $rentalDriver);
        }

        $requests = $this->linkedRequests($tripLog);

        if ($tripLog->trip_status !== 'in_progress') {
            throw ValidationException::withMessages(['trip' => 'Trip is not in progress.']);
        }

        $tripLog->loadMissing('vehicle');
        $vehicle = $tripLog->vehicle;

        if ($vehicle->isRental()) {
            $endKm = null;
            $totalKm = null;
        } else {
            $totalKm = $this->validateEndKm($tripLog, $endKm);
        }

        return DB::transaction(function () use ($tripLog, $requests, $employee, $endKm, $totalKm, $vehicle) {
            $tripLog->update([
                'duty_end_at' => now(),
                'trip_status' => 'completed',
                'end_km'      => $endKm,
                'total_km'    => $totalKm,
                'rental_km_rate'       => null,
                'rental_charge_amount' => 0,
            ]);

            $tripLog = $tripLog->fresh();

            $pay = $this->driverPayCalculator->calculate($tripLog);

            $tripLog->update($pay);

            if ($endKm !== null && ! $vehicle->isRental()) {
                $tripLog->vehicle->update(['last_odometer_km' => $endKm]);
            }

            foreach ($requests as $request) {
                $request->update(['status' => 'completed']);
                app(TransportRequestService::class)->recordStatusChange(
                    $request,
                    'in_progress',
                    'completed',
                    employeeId: $employee?->id
                );
                $this->notifications->tripCompleted($request->fresh(['employee', 'vehicle', 'driver.employee', 'rentalDriver']));
            }

            $tripLog->vehicle->update(['status' => 'available']);

            if ($pay['total_driver_pay'] > 0) {
                TmsDriverOvertimePayment::updateOrCreate(
                    ['trip_log_id' => $tripLog->id],
                    [
                        'driver_id'         => $tripLog->driver_id,
                        'rental_driver_id'  => $tripLog->rental_driver_id,
                        'amount'            => $pay['total_driver_pay'],
                        'payment_breakdown' => [
                            'bill_type'           => $pay['bill_type'],
                            'night_bill_amount'   => $pay['night_bill_amount'],
                            'holiday_duty_amount' => $pay['holiday_duty_amount'],
                            'ot_hours'            => $pay['ot_hours'],
                            'ot_hourly_amount'    => $pay['ot_hourly_amount'],
                        ],
                        'payment_status' => 'pending',
                    ]
                );

                $this->notifications->otPendingPayment($tripLog->fresh(['driver.employee', 'rentalDriver']));
            }

            return $tripLog->fresh();
        });
    }

    public function driverForEmployee(Employee $employee): ?TmsDriver
    {
        return TmsDriver::where('employee_id', $employee->id)
            ->where('factory_id', $employee->factory_id)
            ->where('status', 'active')
            ->first();
    }

    public function isRentalDriverTrip(TmsTripLog $tripLog): bool
    {
        return $tripLog->rental_driver_id !== null;
    }

    /** @return \Illuminate\Support\Collection<int, TmsTransportRequest> */
    private function linkedRequests(TmsTripLog $tripLog): \Illuminate\Support\Collection
    {
        $tripLog->loadMissing('transportRequests');

        if ($tripLog->transportRequests->isNotEmpty()) {
            return $tripLog->transportRequests->whereIn('status', ['approved', 'in_progress']);
        }

        if ($tripLog->transport_request_id) {
            return TmsTransportRequest::where('id', $tripLog->transport_request_id)->get();
        }

        return collect();
    }

    private function assertAssignedCompanyDriver(TmsTripLog $tripLog, Employee $employee): void
    {
        $driver = $this->driverForEmployee($employee);

        if (! $driver || $tripLog->driver_id !== $driver->id) {
            abort(403, 'You are not the assigned driver for this trip.');
        }
    }

    private function assertAssignedRentalDriver(TmsTripLog $tripLog, TmsRentalDriver $rentalDriver): void
    {
        if ((int) $tripLog->rental_driver_id !== (int) $rentalDriver->id) {
            abort(403, 'You are not the assigned driver for this trip.');
        }
    }

    private function validateStartKm(TmsVehicle $vehicle, ?float $startKm): void
    {
        if ($startKm === null) {
            return;
        }

        if ($startKm < (float) $vehicle->last_odometer_km) {
            throw ValidationException::withMessages([
                'start_km' => 'Start KM must be at least ' . number_format((float) $vehicle->last_odometer_km, 2) . ' (last recorded odometer).',
            ]);
        }
    }

    private function validateEndKm(TmsTripLog $tripLog, ?float $endKm): float
    {
        if ($endKm === null) {
            return 0;
        }

        if ($tripLog->start_km === null) {
            throw ValidationException::withMessages([
                'start_km' => 'Start KM must be recorded before entering end KM.',
            ]);
        }

        if ($endKm <= (float) $tripLog->start_km) {
            throw ValidationException::withMessages([
                'end_km' => 'End KM must be greater than start KM (' . number_format((float) $tripLog->start_km, 2) . ').',
            ]);
        }

        return round($endKm - (float) $tripLog->start_km, 2);
    }
}
