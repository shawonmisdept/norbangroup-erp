<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Building;
use App\Models\Hrm\Floor;
use App\Models\Hrm\Line;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $groupHr;

    private User $unitHr;

    private Factory $factoryA;

    private Factory $factoryB;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');

        $this->factoryA = Factory::create(['name' => 'Unit Alpha', 'is_active' => true]);
        $this->factoryB = Factory::create(['name' => 'Unit Beta', 'is_active' => true]);

        $groupRole = Role::create([
            'name'        => 'Group HR',
            'permissions' => ['hrm.employees.view', 'hrm.attendance.view'],
        ]);

        $unitRole = Role::create([
            'name'        => 'Unit HR',
            'permissions' => ['hrm.employees.view', 'hrm.attendance.view'],
        ]);

        $this->groupHr = User::create([
            'name'     => 'Group HR',
            'email'    => 'group-hr@test.com',
            'password' => 'password',
            'role_id'  => $groupRole->id,
        ]);

        $this->unitHr = User::create([
            'name'       => 'Unit HR',
            'email'      => 'unit-hr@test.com',
            'password'   => 'password',
            'role_id'    => $unitRole->id,
            'factory_id' => $this->factoryA->id,
        ]);

        $department = Department::create(['factory_id' => $this->factoryA->id, 'name' => 'Sewing']);
        $building = Building::create(['factory_id' => $this->factoryA->id, 'name' => 'Main', 'is_active' => true]);
        $floor = Floor::create(['factory_id' => $this->factoryA->id, 'building_id' => $building->id, 'name' => 'GF', 'is_active' => true]);
        $line = Line::create(['factory_id' => $this->factoryA->id, 'floor_id' => $floor->id, 'name' => 'Line 1', 'is_active' => true]);

        $this->employee = Employee::create([
            'factory_id'    => $this->factoryA->id,
            'department_id' => $department->id,
            'line_id'       => $line->id,
            'employee_code' => 'DB-W001',
            'name'          => 'Dashboard Worker',
            'gender'        => 'male',
            'status'        => 'active',
            'joining_date'  => '2026-06-01',
        ]);

        Employee::create([
            'factory_id'    => $this->factoryA->id,
            'department_id' => $department->id,
            'employee_code' => 'DB-W002',
            'name'          => 'Absent Worker',
            'gender'        => 'female',
            'status'        => 'active',
        ]);

        AttendanceDailyLog::create([
            'factory_id'      => $this->factoryA->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => '2026-06-16',
            'check_in'        => '2026-06-16 10:05:00',
            'check_out'       => '2026-06-16 18:00:00',
            'late_minutes'    => 5,
            'status'          => 'late',
        ]);

        AttendanceDailyLog::create([
            'factory_id'      => $this->factoryA->id,
            'employee_id'     => Employee::where('employee_code', 'DB-W002')->value('id'),
            'attendance_date' => '2026-06-16',
            'status'          => 'absent',
        ]);

        Employee::create([
            'factory_id'    => $this->factoryB->id,
            'employee_code' => 'DB-B001',
            'name'          => 'Beta Worker',
            'status'        => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_group_hr_can_view_hrm_dashboard(): void
    {
        $this->actingAs($this->groupHr)
            ->get(route('admin.hrm.dashboard'))
            ->assertOk()
            ->assertSee('HRM Dashboard')
            ->assertSee('Employee Data')
            ->assertSee('Total Present')
            ->assertSee('All Companies');
    }

    public function test_unit_hr_sees_only_assigned_factory(): void
    {
        $this->actingAs($this->unitHr)
            ->get(route('admin.hrm.dashboard'))
            ->assertOk()
            ->assertSee('Employee Data')
            ->assertSee('Total Present')
            ->assertDontSee('All Companies');
    }

    public function test_dashboard_shows_department_breakdown(): void
    {
        $this->actingAs($this->groupHr)
            ->get(route('admin.hrm.dashboard', ['factory_id' => $this->factoryA->id]))
            ->assertOk()
            ->assertSee('Sewing')
            ->assertSee('Department Wise');
    }

    public function test_clicking_present_opens_today_attendance_detail(): void
    {
        $this->actingAs($this->groupHr)
            ->get(route('admin.hrm.dashboard.today-attendance', [
                'type'       => 'present',
                'date'       => '2026-06-16',
                'factory_id' => $this->factoryA->id,
            ]))
            ->assertOk()
            ->assertSee('Total Present')
            ->assertSee('DB-W001')
            ->assertSee('Dashboard Worker')
            ->assertSee('Check In')
            ->assertSee('Official Status')
            ->assertSee('10:05');
    }

    public function test_absent_filter_shows_absent_employees_only(): void
    {
        $this->actingAs($this->groupHr)
            ->get(route('admin.hrm.dashboard.today-attendance', [
                'type'       => 'absent',
                'date'       => '2026-06-16',
                'factory_id' => $this->factoryA->id,
            ]))
            ->assertOk()
            ->assertSee('DB-W002')
            ->assertSee('Absent Worker')
            ->assertDontSee('Dashboard Worker');
    }

    public function test_user_without_hrm_permission_is_denied(): void
    {
        $role = Role::create(['name' => 'No HRM', 'permissions' => ['orders.view']]);
        $user = User::create([
            'name'     => 'Ops Only',
            'email'    => 'ops@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.dashboard'))
            ->assertForbidden();
    }
}
