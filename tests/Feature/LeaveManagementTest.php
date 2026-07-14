<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\LeaveBalance;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\LeaveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Factory $factory;

    private Employee $employee;

    private Employee $manager;

    private EmployeePortalUser $portalUser;

    private EmployeePortalUser $managerPortalUser;

    private LeaveType $casualLeave;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');

        $this->factory = Factory::create(['name' => 'Leave Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Leave Admin',
            'permissions' => ['hrm.leave.view', 'hrm.leave.manage', 'hrm.leave.approve'],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Leave Admin',
            'email'    => 'hr-leave-admin@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Morning',
            'start_time'    => '10:00:00',
            'end_time'      => '18:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $this->manager = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'LV-M001',
            'name'          => 'Line Manager',
            'status'        => 'active',
        ]);

        $this->employee = Employee::create([
            'factory_id'      => $this->factory->id,
            'shift_id'        => $shift->id,
            'employee_code'   => 'LV-P001',
            'name'            => 'Leave Worker',
            'reporting_to_id' => $this->manager->id,
            'status'          => 'active',
        ]);

        $this->portalUser = EmployeePortalUser::create([
            'employee_id' => $this->employee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $this->managerPortalUser = EmployeePortalUser::create([
            'employee_id' => $this->manager->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $this->casualLeave = LeaveType::create([
            'code'              => 'LVT-CL001',
            'name'              => 'Casual Leave (CL)',
            'is_paid'           => true,
            'max_days_per_year' => 10,
            'is_active'         => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_employee_can_apply_for_leave(): void
    {
        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.leave.apply.store'), [
                'leave_type_id' => $this->casualLeave->id,
                'start_date'    => '2026-06-17',
                'end_date'      => '2026-06-18',
                'reason'        => 'Family event',
            ])
            ->assertRedirect(route('employee.leave'))
            ->assertSessionHas('success');

        $application = LeaveApplication::first();
        $this->assertSame(LeaveService::STEP_REPORTING, $application->current_approval_step);

        $this->assertDatabaseHas('hrm_leave_applications', [
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'status'        => 'pending',
            'total_days'    => 2,
        ]);
    }

    public function test_apply_requires_reporting_person(): void
    {
        $this->employee->update(['reporting_to_id' => null]);

        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.leave.apply.store'), [
                'leave_type_id' => $this->casualLeave->id,
                'start_date'    => '2026-06-17',
                'end_date'      => '2026-06-17',
                'reason'        => 'Personal work',
            ])
            ->assertSessionHasErrors();
    }

    public function test_reporting_person_approves_then_hr_approves(): void
    {
        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.leave.apply.store'), [
                'leave_type_id' => $this->casualLeave->id,
                'start_date'    => '2026-06-17',
                'end_date'      => '2026-06-17',
                'reason'        => 'Personal work',
            ]);

        $application = LeaveApplication::first();

        $this->actingAs($this->managerPortalUser, 'employee')
            ->post(route('employee.leave.applications.approve', $application))
            ->assertRedirect()
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame('pending', $application->status);
        $this->assertSame(LeaveService::STEP_HR, $application->current_approval_step);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.leave.transactions.approve', $application))
            ->assertRedirect()
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame('approved', $application->status);

        $this->assertDatabaseHas('hrm_attendance_daily_logs', [
            'employee_id' => $this->employee->id,
            'status'      => 'leave',
        ]);
    }

    public function test_reporting_person_can_approve_via_team_approvals_route(): void
    {
        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.leave.apply.store'), [
                'leave_type_id' => $this->casualLeave->id,
                'start_date'    => '2026-06-18',
                'end_date'      => '2026-06-18',
                'reason'        => 'Team page approve',
            ]);

        $application = LeaveApplication::first();
        $this->assertNotNull($application);

        // Simulate MySQL PDO string attributes that previously tripped strict !== checks.
        $application->setRawAttributes(array_merge($application->getAttributes(), [
            'current_approval_step' => '1',
            'employee_id'           => (string) $application->employee_id,
        ]));

        $this->employee->setRawAttributes(array_merge($this->employee->getAttributes(), [
            'reporting_to_id' => (string) $this->manager->id,
        ]));
        $application->setRelation('employee', $this->employee);

        $this->assertSame('1', $application->getAttributes()['current_approval_step']);
        $this->assertSame(1, $application->current_approval_step);

        $this->actingAs($this->managerPortalUser, 'employee')
            ->from(route('employee.team'))
            ->post(route('employee.team.leave.approve', $application->getKey()))
            ->assertRedirect(route('employee.team'))
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame('pending', $application->status);
        $this->assertSame(LeaveService::STEP_HR, (int) $application->current_approval_step);
    }

    public function test_hr_cannot_approve_before_reporting_person(): void
    {
        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.leave.apply.store'), [
                'leave_type_id' => $this->casualLeave->id,
                'start_date'    => '2026-06-19',
                'end_date'      => '2026-06-19',
                'reason'        => 'Need day off',
            ]);

        $application = LeaveApplication::first();

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.leave.transactions.approve', $application))
            ->assertSessionHasErrors();

        $application->refresh();
        $this->assertSame('pending', $application->status);
    }

    public function test_reporting_person_can_reject_leave(): void
    {
        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.leave.apply.store'), [
                'leave_type_id' => $this->casualLeave->id,
                'start_date'    => '2026-06-19',
                'end_date'      => '2026-06-19',
                'reason'        => 'Need day off',
            ]);

        $application = LeaveApplication::first();

        $this->actingAs($this->managerPortalUser, 'employee')
            ->post(route('employee.leave.applications.reject', $application), [
                'rejection_reason' => 'Staff shortage',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame('rejected', $application->status);
    }

    public function test_employee_can_view_leave_page_with_balances(): void
    {
        LeaveBalance::create([
            'factory_id'    => $this->factory->id,
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'year'          => 2026,
            'entitled_days' => 10,
            'used_days'     => 2,
            'pending_days'  => 1,
        ]);

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.leave'))
            ->assertOk()
            ->assertSee('My Leave')
            ->assertSee('Casual Leave (CL)')
            ->assertSee('+ Apply');
    }

    public function test_user_without_permission_cannot_access_leave_admin(): void
    {
        $role = Role::create(['name' => 'No Leave', 'permissions' => []]);
        $user = User::create([
            'name'     => 'No Leave User',
            'email'    => 'no-leave@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.leave.hub'))
            ->assertForbidden();
    }

    public function test_employee_sees_pending_step_in_leave_history(): void
    {
        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.leave.apply.store'), [
                'leave_type_id' => $this->casualLeave->id,
                'start_date'    => '2026-06-17',
                'end_date'      => '2026-06-17',
                'reason'        => 'Personal',
            ]);

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.leave'))
            ->assertOk()
            ->assertSee('Awaiting Reporting Person')
            ->assertSee($this->manager->name);
    }

    public function test_leave_days_exclude_factory_holidays(): void
    {
        \App\Models\Hrm\Holiday::create([
            'factory_id' => $this->factory->id,
            'name'       => 'Factory Holiday',
            'date'       => '2026-06-17',
            'is_active'  => true,
        ]);

        $service = app(LeaveService::class);
        $days = $service->calculateLeaveDays(
            Carbon::parse('2026-06-17'),
            Carbon::parse('2026-06-18'),
            $this->factory->id
        );

        $this->assertSame(1.0, $days);
    }

    public function test_suspended_employee_cannot_apply_for_leave(): void
    {
        $this->employee->update(['status' => 'suspended']);

        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.leave.apply.store'), [
                'leave_type_id' => $this->casualLeave->id,
                'start_date'    => '2026-06-17',
                'end_date'      => '2026-06-17',
                'reason'        => 'Need leave',
            ])
            ->assertSessionHasErrors();
    }
}
