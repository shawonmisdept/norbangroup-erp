<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Building;
use App\Models\Hrm\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmPriority2ProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_hr_cannot_create_employee_for_other_factory(): void
    {
        $factoryA = Factory::create(['name' => 'Unit A', 'is_active' => true]);
        $factoryB = Factory::create(['name' => 'Unit B', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Unit HR',
            'permissions' => ['hrm.employees.manage', 'hrm.employees.view'],
        ]);

        $user = User::create([
            'name'       => 'Unit HR User',
            'email'      => 'unit-hr@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factoryA->id,
        ]);

        $this->actingAs($user)->post(route('admin.hrm.employees.store'), [
            'factory_id'    => $factoryB->id,
            'employee_code' => 'UB-001',
            'name'          => 'Cross Unit Worker',
            'status'        => 'active',
        ])->assertForbidden();
    }

    public function test_unit_hr_only_sees_own_hrm_master_records(): void
    {
        $factoryA = Factory::create(['name' => 'Master A', 'is_active' => true]);
        $factoryB = Factory::create(['name' => 'Master B', 'is_active' => true]);

        Building::create(['factory_id' => $factoryA->id, 'code' => 'BA', 'name' => 'Building A', 'is_active' => true]);
        Building::create(['factory_id' => $factoryB->id, 'code' => 'BB', 'name' => 'Building B', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Unit HRM Master',
            'permissions' => ['hrm.masters.view', 'hrm.buildings.view'],
        ]);

        $user = User::create([
            'name'       => 'Unit Master HR',
            'email'      => 'unit-master@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factoryA->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.masters.index', ['module' => 'hrm-buildings']))
            ->assertOk()
            ->assertSee('Building A')
            ->assertDontSee('Building B');
    }

    public function test_sync_failures_page_loads_for_sync_permission(): void
    {
        $factory = Factory::create(['name' => 'Sync Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Sync Admin',
            'permissions' => ['hrm.attendance.sync'],
        ]);

        $user = User::create([
            'name'     => 'Sync User',
            'email'    => 'sync-user@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.attendance.sync.failures'))
            ->assertOk()
            ->assertSee('Biometric Sync Failures');
    }

    public function test_sync_device_queues_job_instead_of_blocking(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $factory = Factory::create(['name' => 'Queue Factory', 'is_active' => true]);

        $device = \App\Models\Hrm\BiometricDevice::create([
            'factory_id'    => $factory->id,
            'code'          => 'DEV1',
            'name'          => 'Gate Device',
            'adms_url'      => 'http://device.local/adms',
            'is_active'     => true,
        ]);

        $role = Role::create([
            'name'        => 'Sync Operator',
            'permissions' => ['hrm.attendance.sync'],
        ]);

        $user = User::create([
            'name'     => 'Sync Op',
            'email'    => 'sync-op@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user)
            ->post(route('admin.hrm.attendance.devices.sync', $device))
            ->assertRedirect()
            ->assertSessionHas('success');

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\Hrm\SyncBiometricDeviceJob::class);
    }
}
