<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\LeaveService;
use App\Support\RolePermissionCatalog;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportingManagerApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private Employee $managerEmployee;

    private Employee $reportee;

    private User $managerUser;

    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');

        $this->factory = Factory::create(['name' => 'Approval Factory', 'is_active' => true]);

        $shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Day',
            'start_time'    => '09:00:00',
            'end_time'      => '18:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $this->managerEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'MGR-001',
            'name'          => 'Unit Manager',
            'email'         => 'unit.manager@test.com',
            'status'        => 'active',
        ]);

        $this->reportee = Employee::create([
            'factory_id'      => $this->factory->id,
            'shift_id'        => $shift->id,
            'employee_code'   => 'EMP-001',
            'name'            => 'Reportee',
            'reporting_to_id' => $this->managerEmployee->id,
            'status'          => 'active',
        ]);

        $role = Role::create([
            'name'        => 'Manager',
            'permissions' => RolePermissionCatalog::managerPermissions(),
        ]);

        $this->managerUser = User::create([
            'name'       => 'Unit Manager',
            'email'      => 'unit.manager@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);

        $this->leaveType = LeaveType::create([
            'code'              => 'CL',
            'name'              => 'Casual Leave',
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

    public function test_manager_role_includes_leave_approve_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('name', 'Manager')->firstOrFail();

        $this->assertTrue($role->hasPermission('hrm.leave.approve'));
    }

    public function test_linked_admin_user_can_approve_reporting_step_from_admin_panel(): void
    {
        $application = $this->pendingLeaveApplication();

        $this->actingAs($this->managerUser)
            ->post(route('admin.hrm.leave.transactions.approve-reporting', $application), [
                'notes' => 'Approved from admin',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $application->refresh();

        $this->assertSame('pending', $application->status);
        $this->assertSame(LeaveService::STEP_HR, (int) $application->current_approval_step);
    }

    public function test_admin_user_without_linked_employee_cannot_approve_reporting_step(): void
    {
        $application = $this->pendingLeaveApplication();

        $otherUser = User::create([
            'name'       => 'Other Manager',
            'email'      => 'other.manager@test.com',
            'password'   => 'password',
            'role_id'    => $this->managerUser->role_id,
            'factory_id' => $this->factory->id,
        ]);

        $this->actingAs($otherUser)
            ->post(route('admin.hrm.leave.transactions.approve-reporting', $application))
            ->assertForbidden();
    }

    public function test_manager_with_leave_approve_can_finalize_hr_step(): void
    {
        $application = $this->pendingLeaveApplication();

        app(LeaveService::class)->approveByEmployee($application, $this->managerEmployee);

        $this->actingAs($this->managerUser)
            ->post(route('admin.hrm.leave.transactions.approve', $application->fresh()), [
                'notes' => 'HR approved',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_leave_transactions_index_shows_my_team_pending_count(): void
    {
        $this->pendingLeaveApplication();

        $this->actingAs($this->managerUser)
            ->get(route('admin.hrm.leave.transactions.index'))
            ->assertOk()
            ->assertSee('awaiting your approval as reporting person');
    }

    private function pendingLeaveApplication(): LeaveApplication
    {
        return LeaveApplication::create([
            'factory_id'            => $this->factory->id,
            'employee_id'             => $this->reportee->id,
            'leave_type_id'         => $this->leaveType->id,
            'start_date'            => '2026-06-17',
            'end_date'              => '2026-06-17',
            'total_days'            => 1,
            'reason'                => 'Personal',
            'status'                => 'pending',
            'current_approval_step' => LeaveService::STEP_REPORTING,
            'applied_at'            => now(),
        ]);
    }
}
