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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Factory $factory;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create(['name' => 'Report Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Reports',
            'permissions' => ['hrm.attendance.view'],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Reports',
            'email'    => 'hr-reports@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $department = Department::create(['factory_id' => $this->factory->id, 'name' => 'Sewing']);
        $building = Building::create(['factory_id' => $this->factory->id, 'name' => 'Main', 'is_active' => true]);
        $floor = Floor::create(['factory_id' => $this->factory->id, 'building_id' => $building->id, 'name' => 'GF', 'is_active' => true]);
        $line = Line::create(['factory_id' => $this->factory->id, 'floor_id' => $floor->id, 'name' => 'Line 1', 'is_active' => true]);

        $this->employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'department_id' => $department->id,
            'line_id'       => $line->id,
            'employee_code' => 'RP-W001',
            'name'          => 'Report Worker',
            'status'        => 'active',
        ]);

        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => '2026-06-02',
            'status'          => 'present',
        ]);

        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => '2026-06-03',
            'status'          => 'late',
            'late_minutes'    => 15,
        ]);

        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => '2026-06-04',
            'status'          => 'absent',
        ]);
    }

    public function test_hr_can_view_attendance_reports(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.attendance.reports.index', [
                'factory_id' => $this->factory->id,
                'year'       => 2026,
                'month'      => 6,
            ]))
            ->assertOk()
            ->assertSee('Attendance Reports')
            ->assertSee('Sewing')
            ->assertSee('Line 1')
            ->assertSee('Report Worker');
    }

    public function test_hr_can_view_employee_calendar(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.attendance.reports.employee', [
                'employee' => $this->employee,
                'year'     => 2026,
                'month'    => 6,
            ]))
            ->assertOk()
            ->assertSee('Employee Calendar')
            ->assertSee('Report Worker');
    }

    public function test_hr_can_export_department_csv(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.attendance.reports.export', [
                'factory_id' => $this->factory->id,
                'year'       => 2026,
                'month'      => 6,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
