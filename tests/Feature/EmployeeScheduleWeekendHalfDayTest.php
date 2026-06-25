<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\Employee;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\AttendanceProcessor;
use App\Services\Hrm\EmployeeScheduleService;
use App\Services\Hrm\HalfDayEntryService;
use App\Services\Hrm\PayrollProcessor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeScheduleWeekendHalfDayTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private Shift $shift;

    private Employee $employee;

    private EmployeeScheduleService $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-25 12:00:00');

        $this->factory = Factory::create(['name' => 'Schedule Factory', 'is_active' => true]);
        $this->schedule = app(EmployeeScheduleService::class);

        $this->shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Morning',
            'start_time'    => '10:00:00',
            'end_time'      => '18:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $this->employee = Employee::create([
            'factory_id'         => $this->factory->id,
            'shift_id'           => $this->shift->id,
            'employee_code'      => 'SC-W001',
            'name'               => 'Schedule Worker',
            'status'             => 'active',
            'weekend_days'       => [0, 6],
            'weekend_ot_allowed' => false,
        ]);

        AttendancePolicy::forFactory($this->factory->id);
    }

    public function test_employee_weekend_days_are_respected(): void
    {
        $sunday = Carbon::parse('2026-06-21');
        $saturday = Carbon::parse('2026-06-20');
        $monday = Carbon::parse('2026-06-22');

        $this->assertTrue($this->schedule->isWeekend($this->employee, $sunday));
        $this->assertTrue($this->schedule->isWeekend($this->employee, $saturday));
        $this->assertFalse($this->schedule->isWeekend($this->employee, $monday));
    }

    public function test_mark_absences_creates_off_day_on_weekend(): void
    {
        $period = AttendancePeriod::create([
            'factory_id' => $this->factory->id,
            'year'       => 2026,
            'month'      => 6,
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'open',
        ]);

        $processor = app(AttendanceProcessor::class);
        $count = $processor->markAbsences(
            $this->factory->id,
            Carbon::parse('2026-06-21'),
            Carbon::parse('2026-06-21'),
            $period
        );

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('hrm_attendance_daily_logs', [
            'employee_id' => $this->employee->id,
            'status'      => 'off_day',
        ]);
    }

    public function test_short_work_time_triggers_auto_half_day_with_type(): void
    {
        $date = Carbon::parse('2026-06-23');

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => '1',
            'punched_at'        => $date->copy()->setTime(10, 0),
            'punch_type'        => 'in',
            'source'            => 'adms_push',
        ]);

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => '1',
            'punched_at'        => $date->copy()->setTime(13, 0),
            'punch_type'        => 'out',
            'source'            => 'adms_push',
        ]);

        app(AttendanceProcessor::class)->processDate($this->factory->id, $date);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', $date)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('half_day', $log->status);
        $this->assertSame('first_half', $log->half_day_type);
    }

    public function test_weekend_ot_counts_all_work_minutes_as_ot(): void
    {
        $this->employee->update(['weekend_ot_allowed' => true]);

        $attendancePeriod = AttendancePeriod::create([
            'factory_id' => $this->factory->id,
            'year'       => 2026,
            'month'      => 6,
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'frozen',
            'frozen_at'  => now(),
        ]);

        SalaryStructure::create([
            'employee_id'  => $this->employee->id,
            'factory_id'   => $this->factory->id,
            'pay_type'     => 'wages',
            'daily_wage'   => 800,
            'gross_salary' => 0,
            'is_active'    => true,
        ]);

        AttendanceDailyLog::create([
            'factory_id'           => $this->factory->id,
            'employee_id'          => $this->employee->id,
            'attendance_period_id' => $attendancePeriod->id,
            'attendance_date'      => '2026-06-21',
            'status'               => 'present',
            'work_minutes'         => 480,
        ]);

        $role = Role::create([
            'name'        => 'Payroll HR',
            'permissions' => ['hrm.salary.view', 'hrm.salary.manage'],
        ]);

        $user = User::create([
            'name'     => 'Payroll HR',
            'email'    => 'payroll-hr@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $payrollPeriod = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        $payrollPeriod->update(['attendance_period_id' => $attendancePeriod->id]);

        $run = app(PayrollProcessor::class)->calculatePeriod($payrollPeriod, $user);
        $item = PayrollItem::where('payroll_run_id', $run->id)->first();

        $this->assertNotNull($item);
        $this->assertSame(8.0, (float) $item->ot_hours);
        $this->assertSame(0, $item->present_days);
    }

    public function test_manual_half_day_entry_is_preserved_on_reprocess(): void
    {
        $date = Carbon::parse('2026-06-24');

        app(HalfDayEntryService::class)->apply($this->employee, [
            'attendance_date'    => $date->toDateString(),
            'half_day_type'      => 'second_half',
            'half_day_pay_ratio' => 0.6,
            'notes'              => 'HR approved',
        ], null);

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => '1',
            'punched_at'        => $date->copy()->setTime(10, 0),
            'punch_type'        => 'in',
            'source'            => 'adms_push',
        ]);

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => '1',
            'punched_at'        => $date->copy()->setTime(18, 0),
            'punch_type'        => 'out',
            'source'            => 'adms_push',
        ]);

        app(AttendanceProcessor::class)->processDate($this->factory->id, $date);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', $date)
            ->first();

        $this->assertTrue($log->is_manual_half_day);
        $this->assertSame('second_half', $log->half_day_type);
        $this->assertSame('0.60', number_format((float) $log->half_day_pay_ratio, 2));
    }

    public function test_hr_can_create_half_day_entry_via_form(): void
    {
        $role = Role::create([
            'name'        => 'HR Half Day',
            'permissions' => ['hrm.attendance.view', 'hrm.attendance.manage'],
        ]);

        $user = User::create([
            'name'     => 'HR',
            'email'    => 'hr-half@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user)
            ->post(route('admin.hrm.attendance.half-day-entry.store'), [
                'employee_id'        => $this->employee->id,
                'attendance_date'    => '2026-06-18',
                'half_day_type'      => 'first_half',
                'half_day_pay_ratio' => 0.5,
                'notes'              => 'Early leave',
            ])
            ->assertRedirect(route('admin.hrm.attendance.half-day-entry.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('hrm_attendance_daily_logs', [
            'employee_id'        => $this->employee->id,
            'status'             => 'half_day',
            'half_day_type'      => 'first_half',
            'is_manual_half_day' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
