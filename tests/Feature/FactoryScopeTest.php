<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Role;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryScopeTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factoryA;

    private Factory $factoryB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factoryA = Factory::create(['name' => 'Unit Alpha', 'is_active' => true]);
        $this->factoryB = Factory::create(['name' => 'Unit Beta', 'is_active' => true]);
    }

    public function test_unit_user_cannot_access_other_factory_vehicle(): void
    {
        $user = $this->unitUser($this->factoryA);
        $vehicle = TmsVehicle::create([
            'factory_id'         => $this->factoryB->id,
            'name'               => 'Beta Car',
            'reg_number'         => 'B-001',
            'type'               => 'own',
            'fuel_type'          => 'petrol',
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);

        $this->actingAs($user)
            ->get(route('admin.tms.maintenance.register', $vehicle))
            ->assertForbidden();
    }

    public function test_admin_with_factory_assignment_can_access_all_units(): void
    {
        $adminRole = Role::where('name', 'Administrator')->firstOrFail();
        $admin = User::create([
            'name'       => 'Misconfigured Admin',
            'email'      => 'admin-scoped@test.com',
            'password'   => 'password',
            'role_id'    => $adminRole->id,
            'factory_id' => $this->factoryA->id,
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'         => $this->factoryB->id,
            'name'               => 'Beta Car 2',
            'reg_number'         => 'B-002',
            'type'               => 'own',
            'fuel_type'          => 'petrol',
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tms.maintenance.register', $vehicle))
            ->assertOk();
    }

    public function test_unit_user_can_access_record_with_null_factory_id_authorization(): void
    {
        $user = $this->unitUser($this->factoryA);

        $employee = Employee::create([
            'factory_id'    => $this->factoryA->id,
            'employee_code' => 'A-001',
            'name'          => 'Worker A',
            'status'        => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.employees.show', $employee))
            ->assertOk();
    }

    public function test_group_user_can_filter_by_any_factory(): void
    {
        $role = Role::create([
            'name'        => 'Group HR',
            'permissions' => ['hrm.employees.view'],
        ]);

        $user = User::create([
            'name'     => 'Group HR',
            'email'    => 'group-hr@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        Employee::create([
            'factory_id'    => $this->factoryB->id,
            'employee_code' => 'B-001',
            'name'          => 'Beta Worker',
            'status'        => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.employees.index', ['factory_id' => $this->factoryB->id]))
            ->assertOk()
            ->assertSee('Beta Worker');
    }

    public function test_unit_user_blocked_from_other_factory_query_param(): void
    {
        $user = $this->unitUser($this->factoryA);

        $this->actingAs($user)
            ->get(route('admin.hrm.employees.index', ['factory_id' => $this->factoryB->id]))
            ->assertForbidden();
    }

    private function unitUser(Factory $factory): User
    {
        $role = Role::create([
            'name'        => 'Unit HR',
            'permissions' => ['tms.maintenance.view', 'hrm.employees.view'],
        ]);

        return User::create([
            'name'       => 'Unit HR ' . $factory->name,
            'email'      => 'unit-' . $factory->id . '@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);
    }
}
