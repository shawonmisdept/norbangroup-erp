<?php

namespace App\Services\Tms;

use App\Models\Hrm\Employee;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripService
{
    public function __construct(
        private OvertimeCalculator $overtimeCalculator,
        private TmsNotificationService $notifications,
    ) {}

    public function start(TmsTripLog $tripLog, Employee $employee): TmsTripLog
    {
        $this->assertAssignedDriver($tripLog, $employee);

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

        return DB::transaction(function () use ($tripLog, $requests, $employee) {
            $now = now();

            $tripLog->update([
                'duty_start_at' => $now,
                'trip_status'   => 'in_progress',
            ]);

            foreach ($requests as $request) {
                $request->update(['status' => 'in_progress']);
                app(TransportRequestService::class)->recordStatusChange($request, 'approved', 'in_progress', employeeId: $employee->id);
                $this->notifications->tripStarted($request->fresh(['employee', 'vehicle', 'driver.employee']));
            }

            $tripLog->vehicle->update(['status' => 'on_trip']);

            return $tripLog->fresh(['transportRequests.employee', 'vehicle', 'driver.employee']);
        });
    }

    public function end(TmsTripLog $tripLog, Employee $employee): TmsTripLog
    {
        $this->assertAssignedDriver($tripLog, $employee);

        $requests = $this->linkedRequests($tripLog);

        if ($tripLog->trip_status !== 'in_progress') {
            throw ValidationException::withMessages(['trip' => 'Trip is not in progress.']);
        }

        return DB::transaction(function () use ($tripLog, $requests, $employee) {
            $now = now();

            $tripLog->update([
                'duty_end_at' => $now,
                'trip_status' => 'completed',
            ]);

            $ot = $this->overtimeCalculator->calculate($tripLog->fresh());
            $tripLog->update($ot);

            foreach ($requests as $request) {
                $request->update(['status' => 'completed']);
                app(TransportRequestService::class)->recordStatusChange($request, 'in_progress', 'completed', employeeId: $employee->id);
                $this->notifications->tripCompleted($request->fresh(['employee', 'vehicle', 'driver.employee']));
            }

            $tripLog->vehicle->update(['status' => 'available']);

            if ($ot['ot_amount'] > 0) {
                TmsDriverOvertimePayment::updateOrCreate(
                    ['trip_log_id' => $tripLog->id],
                    [
                        'driver_id'      => $tripLog->driver_id,
                        'amount'         => $ot['ot_amount'],
                        'payment_status' => 'pending',
                    ]
                );

                $this->notifications->otPendingPayment($tripLog->fresh('driver.employee'));
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

    private function assertAssignedDriver(TmsTripLog $tripLog, Employee $employee): void
    {
        $driver = $this->driverForEmployee($employee);

        if (! $driver || $tripLog->driver_id !== $driver->id) {
            abort(403, 'You are not the assigned driver for this trip.');
        }
    }
}
