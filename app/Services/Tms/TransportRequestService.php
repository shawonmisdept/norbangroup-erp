<?php

namespace App\Services\Tms;

use App\Models\Hrm\Employee;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTransportRequestHistory;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransportRequestService
{
    public function __construct(
        private TmsNotificationService $notifications,
    ) {}

    public function submit(Employee $employee, array $data): TmsTransportRequest
    {
        if (! in_array($employee->status, ['active', 'probation'], true)) {
            throw ValidationException::withMessages([
                'employee' => 'Only active employees can submit transport requests.',
            ]);
        }

        $request = TmsTransportRequest::create([
            'factory_id'         => $employee->factory_id,
            'employee_id'        => $employee->id,
            'pickup_location'    => $data['pickup_location'],
            'destination_id'     => $data['destination_id'] ?? null,
            'destination_custom' => $data['destination_custom'] ?? null,
            'pickup_at'          => $data['pickup_at'],
            'purpose'            => $data['purpose'],
            'passenger_count'    => $data['passenger_count'],
            'status'             => 'pending',
        ]);

        $this->recordHistory($request, null, 'pending', employeeId: $employee->id, notes: 'Submitted');

        $this->notifications->requestSubmitted($request);

        return $request;
    }

    public function cancel(TmsTransportRequest $request, Employee $employee): TmsTransportRequest
    {
        if ((int) $request->employee_id !== (int) $employee->id) {
            abort(403);
        }

        return match ($request->status) {
            'pending'  => $this->cancelPending($request, $employee),
            'approved' => $this->cancelApproved($request, $employee),
            default    => throw ValidationException::withMessages([
                'status' => 'This request can no longer be cancelled.',
            ]),
        };
    }

    public function updatePending(Employee $employee, TmsTransportRequest $request, array $data): TmsTransportRequest
    {
        if ((int) $request->employee_id !== (int) $employee->id) {
            abort(403);
        }

        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be edited.',
            ]);
        }

        $request->update([
            'pickup_location'    => $data['pickup_location'],
            'destination_id'     => $data['destination_id'] ?? null,
            'destination_custom' => $data['destination_custom'] ?? null,
            'pickup_at'          => $data['pickup_at'],
            'purpose'            => $data['purpose'],
            'passenger_count'    => $data['passenger_count'],
        ]);

        $this->recordHistory($request, 'pending', 'pending', employeeId: $employee->id, notes: 'Updated by employee');

        return $request->fresh();
    }

    public function adminCancelApproved(TmsTransportRequest $request, User $user, ?string $reason = null): TmsTransportRequest
    {
        if ($request->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Only approved requests can be cancelled by admin.',
            ]);
        }

        $trip = $request->tripLog;

        if (! $trip || $trip->trip_status !== 'not_started') {
            throw ValidationException::withMessages([
                'status' => 'Trip has already started. Use trip abort to force-close.',
            ]);
        }

        return $this->detachApprovedRequestFromTrip(
            $request,
            $trip,
            userId: $user->id,
            notes: $reason ? 'Cancelled by admin: ' . $reason : 'Cancelled by admin before trip start',
        );
    }

    public function reassignTrip(
        TmsTripLog $trip,
        User $user,
        string $driverType,
        ?int $companyDriverId,
        ?int $rentalDriverId,
        ?int $vehicleIdOverride = null,
    ): TmsTripLog {
        if ($trip->trip_status !== 'not_started') {
            throw ValidationException::withMessages([
                'trip' => 'Can only reassign trips that have not started.',
            ]);
        }

        $requests = TmsTransportRequest::with(['employee', 'destination'])
            ->where('trip_log_id', $trip->id)
            ->where('status', 'approved')
            ->get();

        if ($requests->isEmpty()) {
            throw ValidationException::withMessages([
                'trip' => 'No approved requests linked to this trip.',
            ]);
        }

        $factoryId = (int) $trip->factory_id;
        $totalPassengers = (int) $requests->sum('passenger_count');
        $first = $requests->first();

        return DB::transaction(function () use (
            $trip,
            $requests,
            $user,
            $driverType,
            $companyDriverId,
            $rentalDriverId,
            $vehicleIdOverride,
            $factoryId,
            $totalPassengers,
            $first,
        ) {
            if ($driverType === 'company') {
                $driver = TmsDriver::with(['employee', 'defaultVehicle'])->findOrFail($companyDriverId);
                $vehicle = $this->resolveVehicleForCompanyDriver($driver, $vehicleIdOverride, $factoryId);
                $this->validateCompanyAssignment($first, $vehicle, $driver, $totalPassengers);

                $trip->update([
                    'vehicle_id'       => $vehicle->id,
                    'driver_id'          => $driver->id,
                    'rental_driver_id'   => null,
                    'driver_type'        => 'company',
                ]);
                $rentalDriver = null;
            } else {
                $rentalDriver = TmsRentalDriver::with('defaultVehicle')->findOrFail($rentalDriverId);
                $vehicle = $this->resolveVehicleForRentalDriver($rentalDriver, $vehicleIdOverride, $factoryId);
                $this->validateRentalAssignment($first, $vehicle, $rentalDriver, $totalPassengers);
                $driver = null;

                $trip->update([
                    'vehicle_id'       => $vehicle->id,
                    'driver_id'          => null,
                    'rental_driver_id'   => $rentalDriver->id,
                    'driver_type'        => 'rental',
                ]);
            }

            foreach ($requests as $request) {
                $request->update([
                    'vehicle_id'       => $vehicle->id,
                    'driver_id'        => $driver?->id ?? null,
                    'rental_driver_id' => $rentalDriver?->id ?? null,
                ]);

                $this->recordHistory(
                    $request,
                    'approved',
                    'approved',
                    userId: $user->id,
                    notes: 'Reassigned driver/vehicle (trip #' . $trip->id . ')',
                );

                $this->notifications->requestApproved($request->fresh(['employee', 'driver.employee', 'rentalDriver', 'vehicle']));
            }

            return $trip->fresh(['vehicle', 'driver.employee', 'rentalDriver', 'transportRequests.employee']);
        });
    }

    private function cancelPending(TmsTransportRequest $request, Employee $employee): TmsTransportRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be cancelled.',
            ]);
        }

        $request->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->recordHistory($request, 'pending', 'cancelled', employeeId: $employee->id);

        $this->notifications->requestCancelled($request);

        return $request->fresh();
    }

    private function cancelApproved(TmsTransportRequest $request, Employee $employee): TmsTransportRequest
    {
        $trip = $request->tripLog;

        if (! $trip || $trip->trip_status !== 'not_started') {
            throw ValidationException::withMessages([
                'status' => 'Trip has already started. Contact transport admin to cancel.',
            ]);
        }

        return $this->detachApprovedRequestFromTrip(
            $request,
            $trip,
            employeeId: $employee->id,
            notes: 'Cancelled by employee before trip start',
        );
    }

    private function detachApprovedRequestFromTrip(
        TmsTransportRequest $request,
        TmsTripLog $trip,
        ?int $userId = null,
        ?int $employeeId = null,
        ?string $notes = null,
    ): TmsTransportRequest {
        return DB::transaction(function () use ($request, $trip, $userId, $employeeId, $notes) {
            $remaining = TmsTransportRequest::query()
                ->where('trip_log_id', $trip->id)
                ->where('id', '!=', $request->id)
                ->where('status', 'approved')
                ->get();

            $request->update([
                'status'           => 'cancelled',
                'cancelled_at'     => now(),
                'trip_log_id'      => null,
                'vehicle_id'       => null,
                'driver_id'        => null,
                'rental_driver_id' => null,
            ]);

            $this->recordHistory(
                $request,
                'approved',
                'cancelled',
                userId: $userId,
                employeeId: $employeeId,
                notes: $notes,
            );

            if ($remaining->isEmpty()) {
                $trip->delete();
            } else {
                $trip->update([
                    'total_passengers' => (int) $remaining->sum('passenger_count'),
                ]);
            }

            $this->notifications->requestCancelled($request->fresh(['employee', 'driver.employee', 'rentalDriver']));

            return $request->fresh();
        });
    }

    public function approve(
        TmsTransportRequest $request,
        User $user,
        ?int $vehicleId,
        string $driverType,
        ?int $companyDriverId,
        ?int $rentalDriverId,
    ): TmsTripLog {
        return $this->mergeAndApprove(
            [$request->id],
            $user,
            $driverType,
            $companyDriverId,
            $rentalDriverId,
            $vehicleId,
        );
    }

    /** @param  list<int>  $requestIds */
    public function mergeAndApprove(
        array $requestIds,
        User $user,
        string $driverType,
        ?int $companyDriverId,
        ?int $rentalDriverId,
        ?int $vehicleIdOverride = null,
    ): TmsTripLog {
        $requestIds = array_values(array_unique(array_map('intval', $requestIds)));

        if ($requestIds === []) {
            throw ValidationException::withMessages(['request_ids' => 'Select at least one request.']);
        }

        $requests = TmsTransportRequest::with(['employee', 'destination'])
            ->whereIn('id', $requestIds)
            ->get();

        if ($requests->count() !== count($requestIds)) {
            throw ValidationException::withMessages(['request_ids' => 'One or more requests were not found.']);
        }

        $this->assertMergeable($requests);

        $factoryId = (int) $requests->first()->factory_id;
        $totalPassengers = (int) $requests->sum('passenger_count');

        if ($driverType === 'company') {
            $driver = TmsDriver::with(['employee', 'defaultVehicle'])->find($companyDriverId);
            if (! $driver) {
                throw ValidationException::withMessages(['driver_id' => 'Selected driver was not found.']);
            }
            $vehicle = $this->resolveVehicleForCompanyDriver($driver, $vehicleIdOverride, $factoryId);
            $this->validateCompanyAssignment($requests->first(), $vehicle, $driver, $totalPassengers);

            return $this->createTrip($requests, $user, $vehicle, $driver, null, 'company', $totalPassengers);
        }

        $rentalDriver = TmsRentalDriver::with('defaultVehicle')->find($rentalDriverId);
        if (! $rentalDriver) {
            throw ValidationException::withMessages(['rental_driver_id' => 'Selected rental driver was not found.']);
        }
        $vehicle = $this->resolveVehicleForRentalDriver($rentalDriver, $vehicleIdOverride, $factoryId);
        $this->validateRentalAssignment($requests->first(), $vehicle, $rentalDriver, $totalPassengers);

        return $this->createTrip($requests, $user, $vehicle, null, $rentalDriver, 'rental', $totalPassengers);
    }

    /** @param  Collection<int, TmsTransportRequest>  $requests */
    private function createTrip(
        Collection $requests,
        User $user,
        TmsVehicle $vehicle,
        ?TmsDriver $driver,
        ?TmsRentalDriver $rentalDriver,
        string $driverType,
        int $totalPassengers,
    ): TmsTripLog {
        return DB::transaction(function () use ($requests, $user, $vehicle, $driver, $rentalDriver, $driverType, $totalPassengers) {
            $tripLog = TmsTripLog::create([
                'transport_request_id' => $requests->first()->id,
                'factory_id'           => $requests->first()->factory_id,
                'vehicle_id'           => $vehicle->id,
                'driver_id'            => $driver?->id,
                'rental_driver_id'     => $rentalDriver?->id,
                'driver_type'          => $driverType,
                'total_passengers'     => $totalPassengers,
                'trip_status'          => 'not_started',
            ]);

            foreach ($requests as $request) {
                $request->update([
                    'status'           => 'approved',
                    'vehicle_id'       => $vehicle->id,
                    'driver_id'        => $driver?->id,
                    'rental_driver_id' => $rentalDriver?->id,
                    'trip_log_id'      => $tripLog->id,
                    'approved_by'      => $user->id,
                    'approved_at'      => now(),
                ]);

                $note = $requests->count() > 1
                    ? 'Merged and approved (trip #' . $tripLog->id . ', ' . $totalPassengers . ' passengers)'
                    : 'Approved and assigned';

                $this->recordHistory($request, 'pending', 'approved', userId: $user->id, notes: $note);

                $this->notifications->requestApproved($request->fresh(['employee', 'driver.employee', 'rentalDriver', 'vehicle']));
            }

            return $tripLog->fresh(['vehicle', 'driver.employee', 'rentalDriver', 'transportRequests.employee']);
        });
    }

    public function reject(TmsTransportRequest $request, User $user, string $reason): TmsTransportRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Request is not pending.']);
        }

        $request->update([
            'status'           => 'rejected',
            'rejected_by'      => $user->id,
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        $this->recordHistory($request, 'pending', 'rejected', userId: $user->id, notes: $reason);

        $this->notifications->requestRejected($request->fresh('employee'));

        return $request;
    }

    /** @param  Collection<int, TmsTransportRequest>  $requests */
    private function assertMergeable(Collection $requests): void
    {
        if ($requests->contains(fn ($r) => $r->status !== 'pending')) {
            throw ValidationException::withMessages(['request_ids' => 'All selected requests must be pending.']);
        }

        $factoryIds = $requests->pluck('factory_id')->unique();
        if ($factoryIds->count() > 1) {
            throw ValidationException::withMessages(['request_ids' => 'All requests must belong to the same unit.']);
        }

        $destinations = $requests->map(fn ($r) => $r->destination_id ?: 'custom:' . strtolower(trim($r->destination_custom ?? '')))->unique();
        if ($destinations->count() > 1) {
            throw ValidationException::withMessages(['request_ids' => 'All requests must share the same destination.']);
        }

        $dates = $requests->map(fn ($r) => $r->pickup_at?->toDateString())->unique();
        if ($dates->count() > 1) {
            throw ValidationException::withMessages(['request_ids' => 'All requests must be on the same date.']);
        }
    }

    private function resolveVehicleForCompanyDriver(TmsDriver $driver, ?int $overrideId, int $factoryId): TmsVehicle
    {
        if ($overrideId) {
            $vehicle = TmsVehicle::find($overrideId);
            if (! $vehicle) {
                throw ValidationException::withMessages(['vehicle_id' => 'Selected vehicle was not found.']);
            }

            if (! $driver->hasAssignedVehicle($overrideId)) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Selected vehicle is not assigned to this driver.',
                ]);
            }
        } elseif ($primaryVehicleId = $driver->primaryVehicleId()) {
            $vehicle = TmsVehicle::find($primaryVehicleId);
            if (! $vehicle) {
                throw ValidationException::withMessages([
                    'driver_id' => 'Driver primary vehicle is missing. Re-assign vehicles to this driver or pick a vehicle manually.',
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                'driver_id' => 'Driver has no assigned vehicle. Assign vehicles to the driver or provide an override.',
            ]);
        }

        if ((int) $vehicle->factory_id !== $factoryId || (int) $driver->factory_id !== $factoryId) {
            throw ValidationException::withMessages(['vehicle_id' => 'Vehicle must belong to the same unit.']);
        }

        return $vehicle;
    }

    private function resolveVehicleForRentalDriver(TmsRentalDriver $driver, ?int $overrideId, int $factoryId): TmsVehicle
    {
        if ($overrideId) {
            $vehicle = TmsVehicle::find($overrideId);
            if (! $vehicle) {
                throw ValidationException::withMessages(['vehicle_id' => 'Selected vehicle was not found.']);
            }
        } elseif ($driver->default_vehicle_id) {
            $vehicle = TmsVehicle::find($driver->default_vehicle_id);
            if (! $vehicle) {
                throw ValidationException::withMessages([
                    'rental_driver_id' => 'Rental driver default vehicle is missing. Select a vehicle manually.',
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                'rental_driver_id' => 'Rental driver has no default vehicle. Select a vehicle.',
            ]);
        }

        if ((int) $vehicle->factory_id !== $factoryId || (int) $driver->factory_id !== $factoryId) {
            throw ValidationException::withMessages(['vehicle_id' => 'Vehicle must belong to the same unit.']);
        }

        return $vehicle;
    }

    private function validateCompanyAssignment(
        TmsTransportRequest $request,
        TmsVehicle $vehicle,
        TmsDriver $driver,
        ?int $totalPassengers = null,
    ): void {
        $passengers = $totalPassengers ?? $request->passenger_count;

        if ((int) $vehicle->factory_id !== (int) $request->factory_id || (int) $driver->factory_id !== (int) $request->factory_id) {
            throw ValidationException::withMessages(['factory' => 'Vehicle and driver must belong to the same unit.']);
        }

        if (! $vehicle->isAvailable()) {
            throw ValidationException::withMessages(['vehicle_id' => 'Selected vehicle is not available.']);
        }

        if ($passengers > $vehicle->passenger_capacity) {
            throw ValidationException::withMessages([
                'vehicle_id' => "Total passengers ({$passengers}) exceeds vehicle capacity ({$vehicle->passenger_capacity}). Select fewer requests or another driver/vehicle.",
            ]);
        }

        if (! $driver->isActive()) {
            throw ValidationException::withMessages(['driver_id' => 'Selected driver is inactive.']);
        }

        $employee = $driver->employee;
        if (! $employee || ! in_array($employee->status, ['active', 'probation'], true)) {
            throw ValidationException::withMessages(['driver_id' => 'Driver employee is not active.']);
        }

        if (TmsTripLog::where('driver_id', $driver->id)->where('trip_status', 'in_progress')->exists()) {
            throw ValidationException::withMessages(['driver_id' => 'Driver already has an active trip.']);
        }

        if (TmsTripLog::where('vehicle_id', $vehicle->id)->where('trip_status', 'in_progress')->exists()) {
            throw ValidationException::withMessages(['vehicle_id' => 'Vehicle already has an active trip.']);
        }
    }

    private function validateRentalAssignment(
        TmsTransportRequest $request,
        TmsVehicle $vehicle,
        TmsRentalDriver $driver,
        ?int $totalPassengers = null,
    ): void {
        $passengers = $totalPassengers ?? $request->passenger_count;

        if ((int) $vehicle->factory_id !== (int) $request->factory_id || (int) $driver->factory_id !== (int) $request->factory_id) {
            throw ValidationException::withMessages(['factory' => 'Vehicle and driver must belong to the same unit.']);
        }

        if (! $vehicle->isAvailable()) {
            throw ValidationException::withMessages(['vehicle_id' => 'Selected vehicle is not available.']);
        }

        if ($passengers > $vehicle->passenger_capacity) {
            throw ValidationException::withMessages([
                'vehicle_id' => "Total passengers ({$passengers}) exceeds vehicle capacity ({$vehicle->passenger_capacity}).",
            ]);
        }

        if (! $driver->isActive()) {
            throw ValidationException::withMessages(['rental_driver_id' => 'Selected rental driver is inactive.']);
        }

        if (TmsTripLog::where('rental_driver_id', $driver->id)->where('trip_status', 'in_progress')->exists()) {
            throw ValidationException::withMessages(['rental_driver_id' => 'Rental driver already has an active trip.']);
        }

        if (TmsTripLog::where('vehicle_id', $vehicle->id)->where('trip_status', 'in_progress')->exists()) {
            throw ValidationException::withMessages(['vehicle_id' => 'Vehicle already has an active trip.']);
        }
    }

    public function recordStatusChange(
        TmsTransportRequest $request,
        ?string $from,
        string $to,
        ?int $userId = null,
        ?int $employeeId = null,
        ?string $notes = null,
    ): void {
        $this->recordHistory($request, $from, $to, $userId, $employeeId, $notes);
    }

    private function recordHistory(
        TmsTransportRequest $request,
        ?string $from,
        string $to,
        ?int $userId = null,
        ?int $employeeId = null,
        ?string $notes = null,
    ): void {
        TmsTransportRequestHistory::create([
            'transport_request_id'   => $request->id,
            'from_status'            => $from,
            'to_status'              => $to,
            'changed_by_user_id'     => $userId,
            'changed_by_employee_id' => $employeeId,
            'notes'                  => $notes,
            'created_at'             => now(),
        ]);
    }
}
