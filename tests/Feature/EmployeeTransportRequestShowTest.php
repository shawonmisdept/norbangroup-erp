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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTransportRequestShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_sees_rental_driver_on_approved_transport_request(): void
    {
        Carbon::setTestNow('2026-06-24 08:00:00');

        $factory = Factory::create(['name' => 'TMS Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Authority',
            'permissions' => ['tms.requests.approve'],
        ]);

        $authority = User::create([
            'name'       => 'Transport Admin',
            'email'      => 'tms-show@test.com',
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
            'employee_code' => 'REQ-001',
            'name'          => 'Requester',
            'status'        => 'active',
        ]);

        $portalUser = EmployeePortalUser::create([
            'employee_id' => $requester->id,
            'password'    => 'password',
            'is_active'   => true,
        ]);

        $vendor = TmsRentalVendor::create([
            'factory_id' => $factory->id,
            'name'       => 'City Rent',
            'status'     => 'active',
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'         => $factory->id,
            'name'               => 'Rent Hiace',
            'reg_number'         => 'RNT-001',
            'type'               => 'rental',
            'rental_vendor_id'   => $vendor->id,
            'passenger_capacity' => 8,
            'status'             => 'available',
        ]);

        $rentalDriver = TmsRentalDriver::create([
            'factory_id'         => $factory->id,
            'name'               => 'Rental Karim',
            'mobile'             => '01711112222',
            'rental_vendor_id'   => $vendor->id,
            'default_vehicle_id' => $vehicle->id,
            'status'             => 'active',
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $factory->id,
            'employee_id'        => $requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'Airport',
            'pickup_at'          => '2026-06-24 10:00',
            'purpose'            => 'Pickup',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type'      => 'rental',
                'rental_driver_id' => $rentalDriver->id,
                'vehicle_id'       => $vehicle->id,
            ]);

        $transportRequest->refresh();

        $this->actingAs($portalUser, 'employee')
            ->get(route('employee.transport.requests.show', $transportRequest))
            ->assertOk()
            ->assertSee('Assigned Driver')
            ->assertSee('(Rental)')
            ->assertSee('Rental Karim')
            ->assertSee('City Rent')
            ->assertSee('01711112222')
            ->assertSee('Call Rental Karim');
    }
}
