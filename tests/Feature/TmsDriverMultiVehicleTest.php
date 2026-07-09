<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Role;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsDriverMultiVehicleTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $user;

    private Employee $employee;

    private TmsVehicle $vehicleA;

    private TmsVehicle $vehicleB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create(['name' => 'Head Office', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Driver Admin',
            'permissions' => ['tms.drivers.view', 'tms.drivers.manage'],
        ]);

        $this->user = User::create([
            'name'       => 'Driver Admin',
            'email'      => 'driver-admin@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);

        $this->employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'DRV-MV-1',
            'name'          => 'Ismail Sarkar',
            'status'        => 'active',
        ]);

        $this->vehicleA = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Mercedes Benz',
            'reg_number'         => 'DM-GA-42-0117',
            'type'               => 'own',
            'fuel_type'          => 'diesel',
            'passenger_capacity' => 5,
            'status'             => 'available',
        ]);

        $this->vehicleB = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Toyota Axio',
            'reg_number'         => 'DM-GA-23-5772',
            'type'               => 'own',
            'fuel_type'          => 'petrol',
            'passenger_capacity' => 5,
            'status'             => 'available',
        ]);
    }

    public function test_driver_can_be_assigned_multiple_vehicles_with_primary(): void
    {
        $this->actingAs($this->user)
            ->post(route('admin.tms.drivers.store'), [
                'factory_id'         => $this->factory->id,
                'employee_id'        => $this->employee->id,
                'vehicle_ids'        => [$this->vehicleA->id, $this->vehicleB->id],
                'primary_vehicle_id' => $this->vehicleA->id,
                'ot_rate'            => 100,
                'status'             => 'active',
            ])
            ->assertRedirect(route('admin.tms.drivers.index'));

        $driver = TmsDriver::first();
        $this->assertNotNull($driver);
        $this->assertSame($this->vehicleA->id, $driver->default_vehicle_id);
        $this->assertSame($this->vehicleA->id, $driver->primaryVehicleId());
        $this->assertSame(
            [$this->vehicleA->id, $this->vehicleB->id],
            $driver->assignedVehicleIds()
        );
        $this->assertTrue($driver->hasAssignedVehicle($this->vehicleB->id));
    }

    public function test_same_employee_cannot_be_registered_as_driver_twice(): void
    {
        TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->employee->id,
            'default_vehicle_id' => $this->vehicleA->id,
            'status'             => 'active',
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.tms.drivers.store'), [
                'factory_id'         => $this->factory->id,
                'employee_id'        => $this->employee->id,
                'vehicle_ids'        => [$this->vehicleB->id],
                'primary_vehicle_id' => $this->vehicleB->id,
                'ot_rate'            => 100,
                'status'             => 'active',
            ])
            ->assertSessionHasErrors('employee_id');
    }

    public function test_driver_update_syncs_vehicle_assignments(): void
    {
        $driver = TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->employee->id,
            'default_vehicle_id' => $this->vehicleA->id,
            'status'             => 'active',
        ]);

        $driver->syncAssignedVehicles([$this->vehicleA->id], $this->vehicleA->id);

        $this->actingAs($this->user)
            ->put(route('admin.tms.drivers.update', $driver), [
                'factory_id'         => $this->factory->id,
                'employee_id'        => $this->employee->id,
                'vehicle_ids'        => [$this->vehicleA->id, $this->vehicleB->id],
                'primary_vehicle_id' => $this->vehicleB->id,
                'ot_rate'            => 100,
                'status'             => 'active',
            ])
            ->assertRedirect(route('admin.tms.drivers.index'));

        $driver->refresh()->load('vehicles');

        $this->assertSame($this->vehicleB->id, $driver->primaryVehicleId());
        $this->assertSame(
            [$this->vehicleA->id, $this->vehicleB->id],
            $driver->assignedVehicleIds()
        );
    }

    public function test_vehicle_shows_assigned_driver_from_pivot(): void
    {
        $driver = TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->employee->id,
            'default_vehicle_id' => $this->vehicleA->id,
            'status'             => 'active',
        ]);

        $driver->syncAssignedVehicles([$this->vehicleA->id, $this->vehicleB->id], $this->vehicleA->id);

        $this->vehicleB->load('assignedCompanyDrivers.employee');

        $this->assertStringContainsString('Ismail Sarkar', $this->vehicleB->assignedDriverNames());
    }
}
