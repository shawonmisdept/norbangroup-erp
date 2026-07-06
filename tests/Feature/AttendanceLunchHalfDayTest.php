<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Shift;
use App\Services\Hrm\AttendanceProcessor;
use App\Services\Hrm\ShiftWorkCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceLunchHalfDayTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private Shift $shift;

    private Employee $employee;

    private AttendanceProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create(['name' => 'Lunch Factory', 'is_active' => true]);

        $this->shift = Shift::create([
            'factory_id'       => $this->factory->id,
            'name'             => 'Office',
            'start_time'       => '09:45:00',
            'end_time'         => '19:00:00',
            'break_minutes'    => 60,
            'break_start_time' => '13:00:00',
            'break_end_time'   => '14:00:00',
            'is_active'        => true,
        ]);

        $this->employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $this->shift->id,
            'employee_code' => 'LN-001',
            'name'          => 'Lunch Worker',
            'status'        => 'active',
        ]);

        AttendancePolicy::forFactory($this->factory->id);
        $this->processor = app(AttendanceProcessor::class);
    }

    public function test_employee_shift_relation_is_used_for_lunch_rules(): void
    {
        $this->employee->load('shift');

        $this->assertTrue($this->employee->relationLoaded('shift'));
        $this->assertSame($this->shift->id, $this->employee->shift->id);
        $this->assertSame('13:00:00', $this->employee->shift->break_start_time);
    }

    public function test_work_minutes_exclude_lunch_overlap_for_full_day(): void
    {
        $date = Carbon::parse('2026-07-07');
        $checkIn = $date->copy()->setTime(9, 45);
        $checkOut = $date->copy()->setTime(19, 0);

        $minutes = app(ShiftWorkCalculator::class)->workMinutes($checkIn, $checkOut, $date, $this->shift);

        $this->assertSame(495, $minutes);
    }

    public function test_check_in_after_lunch_is_auto_half_day_second_half(): void
    {
        $date = Carbon::parse('2026-07-07');

        $this->seedPunches($date, '13:15:00', '19:00:00');
        $this->processor->processDate($this->factory->id, $date);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)->first();

        $this->assertNotNull($log);
        $this->assertSame('half_day', $log->status);
        $this->assertSame('second_half', $log->half_day_type);
        $this->assertGreaterThan(0, $log->late_minutes);
        $this->assertStringContainsString('Late', $log->displayStatusLabel());
        $this->assertSame($this->shift->id, $log->shift_id);
        $this->assertSame($this->shift->id, $log->shift->id);
    }

    public function test_check_out_during_lunch_is_auto_half_day_first_half(): void
    {
        $date = Carbon::parse('2026-07-07');

        $this->seedPunches($date, '10:05:00', '13:30:00');
        $this->processor->processDate($this->factory->id, $date);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)->first();

        $this->assertNotNull($log);
        $this->assertSame('half_day', $log->status);
        $this->assertSame('first_half', $log->half_day_type);
        $this->assertGreaterThan(0, $log->late_minutes);
        $this->assertSame(175, $log->work_minutes);
    }

    public function test_on_time_full_day_remains_present(): void
    {
        $date = Carbon::parse('2026-07-07');

        $this->seedPunches($date, '09:45:00', '19:00:00');
        $this->processor->processDate($this->factory->id, $date);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)->first();

        $this->assertNotNull($log);
        $this->assertSame('present', $log->status);
        $this->assertNull($log->half_day_type);
        $this->assertSame(0, $log->late_minutes);
        $this->assertSame(495, $log->work_minutes);
    }

    public function test_late_before_lunch_without_leaving_during_lunch_stays_late_not_half_day(): void
    {
        $date = Carbon::parse('2026-07-07');

        $this->seedPunches($date, '10:30:00', '19:00:00');
        $this->processor->processDate($this->factory->id, $date);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)->first();

        $this->assertNotNull($log);
        $this->assertSame('late', $log->status);
        $this->assertNull($log->half_day_type);
        $this->assertGreaterThan(0, $log->late_minutes);
    }

    private function seedPunches(Carbon $date, string $checkIn, string $checkOut): void
    {
        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => $this->employee->employee_code,
            'punched_at'        => $date->copy()->setTimeFromTimeString($checkIn),
            'punch_type'        => 'in',
            'source'            => 'mobile_gps',
        ]);

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => $this->employee->employee_code,
            'punched_at'        => $date->copy()->setTimeFromTimeString($checkOut),
            'punch_type'        => 'out',
            'source'            => 'mobile_gps',
        ]);
    }
}
