<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\Shift;
use App\Services\Hrm\EmployeeCheckInStatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCheckInWidgetTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private Employee $employee;

    private EmployeePortalUser $portalUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create([
            'name'                   => 'Widget Factory',
            'is_active'              => true,
            'mobile_checkin_enabled' => true,
        ]);

        $shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Day',
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'break_minutes' => 0,
            'is_active'     => true,
        ]);

        $this->employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'W-001',
            'name'          => 'Widget Worker',
            'shift_id'      => $shift->id,
            'status'        => 'active',
        ]);

        $this->portalUser = EmployeePortalUser::create([
            'employee_id' => $this->employee->id,
            'password'    => 'secret-password',
            'is_active'   => true,
        ]);
    }

    public function test_dashboard_shows_check_in_quick_action(): void
    {
        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Check In')
            ->assertSee(route('employee.attendance.check-in', [], false));
    }

    public function test_status_service_reports_active_shift_after_check_in(): void
    {
        Carbon::setTestNow('2026-07-06 11:30:00');

        AttendanceDailyLog::create([
            'factory_id'       => $this->factory->id,
            'employee_id'      => $this->employee->id,
            'attendance_date'  => today(),
            'check_in'         => now()->setTime(9, 2),
            'status'           => 'present',
        ]);

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => $this->employee->employee_code,
            'punched_at'        => now()->setTime(9, 2),
            'punch_type'        => 'in',
            'source'            => 'mobile_gps',
        ]);

        $status = app(EmployeeCheckInStatusService::class)->forEmployee($this->employee);

        $this->assertSame('active', $status['status']);
        $this->assertSame('out', $status['next_action']);
        $this->assertSame('9:02 AM', $status['check_in_label']);
        $this->assertSame(480, $status['shift_minutes']);

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('9:02 AM');

        Carbon::setTestNow();
    }

    public function test_status_service_reports_done_after_check_out(): void
    {
        Carbon::setTestNow('2026-07-06 18:00:00');

        AttendanceDailyLog::create([
            'factory_id'       => $this->factory->id,
            'employee_id'      => $this->employee->id,
            'attendance_date'  => today(),
            'check_in'         => now()->setTime(9, 2),
            'check_out'        => now()->setTime(17, 5),
            'work_minutes'     => 483,
            'status'           => 'present',
        ]);

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => $this->employee->employee_code,
            'punched_at'        => now()->setTime(17, 5),
            'punch_type'        => 'out',
            'source'            => 'mobile_gps',
        ]);

        $status = app(EmployeeCheckInStatusService::class)->forEmployee($this->employee);

        $this->assertSame('done', $status['status']);
        $this->assertSame('done', $status['next_action']);

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.attendance'))
            ->assertOk()
            ->assertSee('9:02 AM')
            ->assertSee('5:05 PM');

        Carbon::setTestNow();
    }
}
