<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\LeaveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class HrmPriority1NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_apply_notifies_manager_portal_and_hr_admin(): void
    {
        Carbon::setTestNow('2026-06-16 10:00:00');
        NotificationFacade::fake();

        $factory = Factory::create(['name' => 'Notify Factory', 'is_active' => true]);

        $hrRole = Role::create([
            'name'        => 'HR Notify',
            'permissions' => ['hrm.leave.view'],
        ]);

        $hrUser = User::create([
            'name'     => 'HR Notify User',
            'email'    => 'hr-notify@test.com',
            'password' => 'password',
            'role_id'  => $hrRole->id,
        ]);

        $shift = Shift::create([
            'factory_id'    => $factory->id,
            'name'          => 'Morning',
            'start_time'    => '10:00:00',
            'end_time'      => '18:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $manager = Employee::create([
            'factory_id'    => $factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'NT-M001',
            'name'          => 'Notify Manager',
            'email'         => 'manager@test.com',
            'status'        => 'active',
        ]);

        EmployeePortalUser::create([
            'employee_id' => $manager->id,
            'password'    => 'password',
            'is_active'   => true,
        ]);

        $employee = Employee::create([
            'factory_id'      => $factory->id,
            'shift_id'        => $shift->id,
            'employee_code'   => 'NT-P001',
            'name'            => 'Notify Worker',
            'reporting_to_id' => $manager->id,
            'status'          => 'active',
        ]);

        $leaveType = LeaveType::create([
            'factory_id' => $factory->id,
            'name'       => 'Casual',
            'code'       => 'CL',
            'is_paid'    => false,
            'is_active'  => true,
        ]);

        app(LeaveService::class)->apply($employee, [
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2026-06-20',
            'end_date'      => '2026-06-20',
            'reason'        => 'Personal',
        ]);

        NotificationFacade::assertSentTo($hrUser, \App\Notifications\LeaveAppliedAdminNotification::class);
        NotificationFacade::assertSentTo($manager->portalUser, \App\Notifications\PortalLeaveApprovalRequiredNotification::class);
    }

    public function test_service_history_recorded_on_employee_update(): void
    {
        $factory = Factory::create(['name' => 'History Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Employee Admin',
            'permissions' => ['hrm.employees.manage', 'hrm.employees.view'],
        ]);

        $user = User::create([
            'name'     => 'HR History',
            'email'    => 'hr-history@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'HS-001',
            'name'          => 'History Worker',
            'status'        => 'probation',
        ]);

        $this->actingAs($user)->put(route('admin.hrm.employees.update', $employee), [
            'employee_code' => 'HS-001',
            'name'          => 'History Worker',
            'factory_id'    => $factory->id,
            'status'        => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_employee_service_histories', [
            'employee_id' => $employee->id,
            'event_type'  => 'status',
        ]);
    }

    public function test_employee_id_card_page_loads(): void
    {
        $factory = Factory::create(['name' => 'Card Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Card View',
            'permissions' => ['hrm.employees.view'],
        ]);

        $user = User::create([
            'name'     => 'HR Card',
            'email'    => 'hr-card@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'ID-001',
            'name'          => 'Card Worker',
            'status'        => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.employees.id-card', $employee))
            ->assertOk()
            ->assertSee('Card Worker')
            ->assertSee('ID-001');
    }
}
