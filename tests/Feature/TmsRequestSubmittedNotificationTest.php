<?php

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\Tms\TmsTransportRequest;
use App\Models\User;
use App\Services\Tms\TransportRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsRequestSubmittedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_submit_notifies_primary_admin_even_when_portal_user_id_matches(): void
    {
        $factory = Factory::create(['name' => 'Notify Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Transport Approver',
            'permissions' => ['tms.requests.approve'],
        ]);

        $admin = User::create([
            'id'         => 1,
            'name'       => 'Primary Admin',
            'email'      => 'primary-admin@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);

        $shift = Shift::create([
            'factory_id'    => $factory->id,
            'name'          => 'Day',
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'NT-E001',
            'name'          => 'Requester',
            'status'        => 'active',
        ]);

        $portalUser = EmployeePortalUser::create([
            'id'          => 1,
            'employee_id' => $employee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $this->actingAs($admin);
        $this->actingAs($portalUser, 'employee')
            ->post(route('employee.transport.requests.store'), [
                'pickup_location'    => 'Gate',
                'destination_custom' => 'Airport',
                'pickup_at'          => now()->addDay()->format('Y-m-d H:i'),
                'purpose'            => 'Visit',
                'passenger_count'    => 1,
            ])
            ->assertRedirect(route('employee.transport.index'));

        $this->assertTrue(
            $admin->fresh()->notifications()->where('data->type', 'tms_request_submitted')->exists()
        );
    }

    public function test_employee_submit_notifies_unit_scoped_admin(): void
    {
        $factory = Factory::create(['name' => 'Notify Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Transport Approver',
            'permissions' => ['tms.requests.approve'],
        ]);

        $admin = User::create([
            'name'       => 'Transport Admin',
            'email'      => 'tms-notify@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);

        $shift = Shift::create([
            'factory_id'    => $factory->id,
            'name'          => 'Day',
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'NT-E001',
            'name'          => 'Requester',
            'status'        => 'active',
        ]);

        $portalUser = EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $this->actingAs($portalUser, 'employee')
            ->post(route('employee.transport.requests.store'), [
                'pickup_location'    => 'Gate',
                'destination_custom' => 'Airport',
                'pickup_at'          => now()->addDay()->format('Y-m-d H:i'),
                'purpose'            => 'Visit',
                'passenger_count'    => 1,
            ])
            ->assertRedirect(route('employee.transport.index'));

        $this->assertTrue(
            $admin->fresh()->notifications()->where('data->type', 'tms_request_submitted')->exists()
        );
    }

    public function test_employee_submit_notifies_cross_unit_admin(): void
    {
        $factory = Factory::create(['name' => 'Unit Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Group Transport Admin',
            'permissions' => ['tms.requests.approve', 'settings.manage'],
        ]);

        $admin = User::create([
            'name'       => 'Group Admin',
            'email'      => 'group-tms@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => null,
        ]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'GF-E001',
            'name'          => 'Worker',
            'status'        => 'active',
        ]);

        $portalUser = EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $this->actingAs($portalUser, 'employee');

        app(TransportRequestService::class)->submit($employee, [
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => now()->addDay(),
            'purpose'            => 'Work',
            'passenger_count'    => 1,
        ]);

        $this->assertTrue(
            $admin->fresh()->notifications()->where('data->type', 'tms_request_submitted')->exists()
        );
    }
}
