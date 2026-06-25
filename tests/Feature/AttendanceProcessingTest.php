<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\AttendanceProcessor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceProcessingTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Factory $factory;

    private Employee $employee;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create(['name' => 'Process Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Attendance Admin',
            'permissions' => ['hrm.attendance.view', 'hrm.attendance.manage', 'hrm.attendance.sync'],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Attendance Admin',
            'email'    => 'hr-att-admin@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->shift = Shift::create([
            'factory_id'     => $this->factory->id,
            'name'           => 'Morning',
            'start_time'     => '10:00:00',
            'end_time'       => '18:00:00',
            'break_minutes'  => 60,
            'is_active'      => true,
        ]);

        $this->employee = Employee::create([
            'factory_id'        => $this->factory->id,
            'shift_id'          => $this->shift->id,
            'employee_code'     => 'M-P001',
            'name'              => 'Process Worker',
            'biometric_user_id' => '501',
            'status'            => 'active',
        ]);
    }

    public function test_processor_creates_daily_log_from_raw_punches(): void
    {
        $date = Carbon::parse('2026-06-20');

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => '501',
            'punched_at'        => $date->copy()->setTime(10, 15),
            'punch_type'        => 'in',
            'source'            => 'adms_push',
        ]);

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => '501',
            'punched_at'        => $date->copy()->setTime(18, 10),
            'punch_type'        => 'out',
            'source'            => 'adms_push',
        ]);

        $processor = app(AttendanceProcessor::class);
        $processed = $processor->processDate($this->factory->id, $date);

        $this->assertSame(1, $processed);

        $log = AttendanceDailyLog::first();
        $this->assertNotNull($log);
        $this->assertSame('late', $log->status);
        $this->assertSame(15, $log->late_minutes);
        $this->assertSame(2, $log->punch_count);
        $this->assertGreaterThan(0, $log->work_minutes);
    }

    public function test_hr_can_process_period_and_freeze(): void
    {
        Carbon::setTestNow('2026-06-20 12:00:00');

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => '501',
            'punched_at'        => now()->setTime(9, 55),
            'punch_type'        => 'in',
            'source'            => 'adms_push',
        ]);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.attendance.process'), [
                'factory_id'    => $this->factory->id,
                'year'          => 2026,
                'month'         => 6,
                'mark_absences' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('hrm_attendance_daily_logs', [
            'employee_id' => $this->employee->id,
            'status'      => 'present',
        ]);

        $period = \App\Models\Hrm\AttendancePeriod::first();
        $this->assertNotNull($period);
        $this->assertSame('processed', $period->status);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.attendance.periods.freeze', $period))
            ->assertRedirect()
            ->assertSessionHas('success');

        $period->refresh();
        $this->assertSame('frozen', $period->status);

        Carbon::setTestNow();
    }

    public function test_employee_can_view_own_attendance(): void
    {
        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => today(),
            'check_in'        => now()->setTime(10, 0),
            'check_out'       => now()->setTime(18, 0),
            'work_minutes'    => 420,
            'status'          => 'present',
        ]);

        $portalUser = EmployeePortalUser::create([
            'employee_id' => $this->employee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $this->actingAs($portalUser, 'employee')
            ->get(route('employee.attendance'))
            ->assertOk()
            ->assertSee('My Attendance')
            ->assertSee('Present');
    }
}
