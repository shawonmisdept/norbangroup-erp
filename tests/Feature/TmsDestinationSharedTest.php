<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Role;
use App\Models\Tms\TmsDestination;
use App\Models\Tms\TmsTransportRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsDestinationSharedTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factoryA;

    private Factory $factoryB;

    private User $admin;

    private Employee $employeeB;

    private EmployeePortalUser $portalB;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-14 10:00:00');

        $this->factoryA = Factory::create(['name' => 'Unit A', 'is_active' => true]);
        $this->factoryB = Factory::create(['name' => 'Unit B', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Destinations',
            'permissions' => ['tms.settings.view', 'tms.settings.manage'],
        ]);

        $this->admin = User::create([
            'name'       => 'Dest Admin',
            'email'      => 'dest-admin@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factoryA->id,
        ]);

        $this->employeeB = Employee::create([
            'factory_id'    => $this->factoryB->id,
            'employee_code' => 'EMP-B1',
            'name'          => 'Worker B',
            'status'        => 'active',
        ]);

        $this->portalB = EmployeePortalUser::create([
            'employee_id' => $this->employeeB->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);
    }

    public function test_destination_is_created_without_unit_and_listed_globally(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tms.destinations.store'), [
                'name'      => 'Airport',
                'address'   => 'Hazrat Shahjalal',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.tms.destinations.index'));

        $destination = TmsDestination::first();
        $this->assertNotNull($destination);
        $this->assertSame('Airport', $destination->name);
        $this->assertTrue($destination->is_active);

        $this->actingAs($this->admin)
            ->get(route('admin.tms.destinations.index'))
            ->assertOk()
            ->assertSee('Airport')
            ->assertDontSee('>Unit A<', false)
            ->assertSee('all units', false);
    }

    public function test_employee_from_any_unit_can_use_shared_destination(): void
    {
        $destination = TmsDestination::create([
            'factory_id' => $this->factoryA->id,
            'name'       => 'CGP Port',
            'is_active'  => true,
        ]);

        $this->actingAs($this->portalB, 'employee')
            ->get(route('employee.transport.requests.create'))
            ->assertOk()
            ->assertSee('CGP Port');

        $this->actingAs($this->portalB, 'employee')
            ->post(route('employee.transport.requests.store'), [
                'pickup_location' => 'Gate 1',
                'destination_id'  => $destination->id,
                'pickup_at'       => now()->addHour()->format('Y-m-d H:i'),
                'purpose'         => 'Shipment',
                'passenger_count' => 2,
            ])
            ->assertRedirect(route('employee.transport.index'));

        $request = TmsTransportRequest::first();
        $this->assertNotNull($request);
        $this->assertSame((int) $this->factoryB->id, (int) $request->factory_id);
        $this->assertSame((int) $destination->id, (int) $request->destination_id);
    }

    public function test_destination_name_must_be_unique_globally(): void
    {
        TmsDestination::create([
            'factory_id' => $this->factoryA->id,
            'name'       => 'Embassy',
            'is_active'  => true,
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.tms.destinations.create'))
            ->post(route('admin.tms.destinations.store'), [
                'name'      => 'Embassy',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.tms.destinations.create'))
            ->assertSessionHasErrors('name');
    }
}
