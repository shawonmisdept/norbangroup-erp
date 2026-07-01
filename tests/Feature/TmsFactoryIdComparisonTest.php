<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Role;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsRentalVendor;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use App\Services\Tms\TransportRequestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsFactoryIdComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_rental_driver_approve_succeeds_for_matching_head_office_unit(): void
    {
        Carbon::setTestNow('2026-07-01 10:00:00');

        $factory = Factory::create(['name' => 'Head Office', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Authority',
            'permissions' => ['tms.requests.approve'],
        ]);

        $authority = User::create([
            'name'       => 'Transport Admin',
            'email'      => 'tms-factory-cast@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);

        TmsSetting::create(array_merge(
            ['factory_id' => $factory->id],
            TmsSetting::defaultValues()
        ));

        $requester = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'HO-1053',
            'name'          => 'Shawonoor Rahman',
            'status'        => 'active',
        ]);

        $vendor = TmsRentalVendor::create([
            'factory_id' => $factory->id,
            'name'       => 'Shamim Rent-A-Car',
            'status'     => 'active',
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'         => $factory->id,
            'name'               => 'Toyota',
            'reg_number'         => 'DM-16-2020',
            'type'               => 'rental',
            'rental_vendor_id'   => $vendor->id,
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);

        $rentalDriver = TmsRentalDriver::create([
            'factory_id'         => $factory->id,
            'name'               => 'Nazrul',
            'mobile'             => '01950990961',
            'rental_vendor_id'   => $vendor->id,
            'default_vehicle_id' => $vehicle->id,
            'status'             => 'active',
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $factory->id,
            'employee_id'        => $requester->id,
            'pickup_location'    => 'Head Office',
            'destination_custom' => 'HAL',
            'pickup_at'          => '2026-07-01 19:33:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type'      => 'rental',
                'rental_driver_id' => $rentalDriver->id,
            ])
            ->assertRedirect();

        $trip = TmsTripLog::first();
        $this->assertNotNull($trip);
        $this->assertSame('not_started', $trip->trip_status);
        $this->assertSame($rentalDriver->id, $trip->rental_driver_id);
    }

    public function test_transport_service_accepts_matching_owner_ids_as_strings(): void
    {
        $factory = Factory::create(['name' => 'Head Office', 'is_active' => true]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => '1053',
            'name'          => 'Shawonoor Rahman',
            'status'        => 'active',
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $factory->id,
            'employee_id'        => $employee->id,
            'pickup_location'    => 'Head Office',
            'destination_custom' => 'HAL',
            'pickup_at'          => '2026-07-01 19:33:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $transportRequest->setRawAttributes(array_merge(
            $transportRequest->getAttributes(),
            ['employee_id' => (string) $employee->id]
        ));
        $employee->setRawAttributes(array_merge(
            $employee->getAttributes(),
            ['id' => (string) $employee->id]
        ));

        $result = app(TransportRequestService::class)->cancel($transportRequest, $employee);

        $this->assertSame('cancelled', $result->fresh()->status);
    }

    public function test_employee_can_view_own_transport_request(): void
    {
        $factory = Factory::create(['name' => 'Head Office', 'is_active' => true]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => '1053',
            'name'          => 'Shawonoor Rahman',
            'status'        => 'active',
        ]);

        $portalUser = EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => 'password',
            'is_active'   => true,
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $factory->id,
            'employee_id'        => $employee->id,
            'pickup_location'    => 'Head Office',
            'destination_custom' => 'HAL',
            'pickup_at'          => '2026-07-01 19:33:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($portalUser, 'employee')
            ->get(route('employee.transport.requests.show', $transportRequest))
            ->assertOk()
            ->assertSee('HAL');
    }
}
