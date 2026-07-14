<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendanceGatePoint;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\BiometricDevice;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceMultiChannelTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private Employee $employee;

    private BiometricDevice $device;

    private User $hrUser;

    private EmployeePortalUser $portalUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create([
            'name'                   => 'Multi Channel Factory',
            'is_active'              => true,
            'attendance_lat'         => 23.8103000,
            'attendance_lng'         => 90.4125000,
            'attendance_radius_m'    => 500,
            'mobile_checkin_enabled' => true,
        ]);

        $shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'General',
            'start_time'    => '08:00:00',
            'end_time'      => '17:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $this->employee = Employee::create([
            'factory_id'        => $this->factory->id,
            'shift_id'          => $shift->id,
            'employee_code'     => 'MC-001',
            'name'              => 'Multi Worker',
            'biometric_user_id' => '101',
            'status'            => 'active',
        ]);

        $this->device = BiometricDevice::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Main Gate SpeedFace',
            'device_serial' => 'SFV5L-TEST-001',
            'device_model'  => 'ZKTeco SpeedFace V5L',
            'is_active'     => true,
        ]);

        $role = Role::create([
            'name'        => 'HR Multi',
            'permissions' => ['hrm.attendance.view', 'hrm.attendance.manage', 'hrm.attendance.sync'],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Multi',
            'email'    => 'hr-multi@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->portalUser = EmployeePortalUser::create([
            'employee_id' => $this->employee->id,
            'email'       => 'mc001@test.com',
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);
    }

    public function test_speedface_iclock_push_creates_instant_daily_log(): void
    {
        $body = "101\t2026-06-20 08:05:00\t0\t15\t0\t0\t0\t\t\t43\n";

        $this->call('POST', '/iclock/cdata?SN=SFV5L-TEST-001&table=ATTLOG', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
        ], $body)
            ->assertOk()
            ->assertSee('OK');

        $this->assertDatabaseHas('hrm_attendance_raw_punches', [
            'source'            => 'iclock_push',
            'biometric_user_id' => '101',
            'employee_id'       => $this->employee->id,
        ]);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('present', $log->status);
        $this->assertNotNull($log->check_in);
    }

    public function test_iclock_duplicate_punch_is_skipped(): void
    {
        $body = "101\t2026-06-20 08:05:00\t0\t15\t0\t0\t0\t\t\t43\n";

        $this->call('POST', '/iclock/cdata?SN=SFV5L-TEST-001&table=ATTLOG', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
        ], $body);
        $this->call('POST', '/iclock/cdata?SN=SFV5L-TEST-001&table=ATTLOG', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
        ], $body);

        $this->assertSame(1, AttendanceRawPunch::count());
    }

    public function test_employee_mobile_check_in_with_geofence(): void
    {
        Carbon::setTestNow('2026-06-24 08:05:00');
        \Illuminate\Support\Facades\Storage::fake('public');

        $photo = 'data:image/jpeg;base64,' . base64_encode(str_repeat('x', 100));

        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.attendance.check-in.store'), [
                'punch_type' => 'in',
                'latitude'   => 23.8104000,
                'longitude'  => 90.4126000,
                'photo'      => $photo,
            ])
            ->assertRedirect(route('employee.dashboard'));

        $this->assertDatabaseHas('hrm_attendance_raw_punches', [
            'employee_id' => $this->employee->id,
            'source'      => 'mobile_gps',
            'punch_type'  => 'in',
        ]);

        $punch = AttendanceRawPunch::first();
        $this->assertNotNull($punch->photo_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($punch->photo_path);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', '2026-06-24')
            ->first();

        $this->assertNotNull($log);
        $this->assertContains($log->status, ['present', 'late']);
    }

    public function test_employee_mobile_check_in_rejected_outside_geofence(): void
    {
        Carbon::setTestNow('2026-06-24 08:20:00');

        $this->factory->update([
            'attendance_lat'      => 23.7790321,
            'attendance_lng'      => 90.4178665,
            'attendance_radius_m' => 10,
        ]);

        $this->actingAs($this->portalUser, 'employee')
            ->from(route('employee.attendance.check-in'))
            ->post(route('employee.attendance.check-in.store'), [
                'punch_type' => 'in',
                'latitude'   => 23.774347,
                'longitude'  => 90.415648,
            ])
            ->assertRedirect(route('employee.attendance.check-in'))
            ->assertSessionHasErrors('latitude');

        $this->assertSame(0, AttendanceRawPunch::count());
        $this->assertSame(0, AttendanceDailyLog::count());
    }

    public function test_mobile_check_in_still_succeeds_if_photo_disk_fails(): void
    {
        Carbon::setTestNow('2026-06-24 08:10:00');

        \Illuminate\Support\Facades\Storage::shouldReceive('disk')
            ->with('public')
            ->andThrow(new \RuntimeException('Disk not writable'));

        $photo = 'data:image/jpeg;base64,' . base64_encode(str_repeat('x', 100));

        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.attendance.check-in.store'), [
                'punch_type' => 'in',
                'latitude'   => 23.8104000,
                'longitude'  => 90.4126000,
                'photo'      => $photo,
            ])
            ->assertRedirect(route('employee.dashboard'));

        $punch = AttendanceRawPunch::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($punch);
        $this->assertNull($punch->photo_path);
        $this->assertSame('in', $punch->punch_type);
    }

    public function test_employee_mobile_check_out_updates_daily_log_after_check_in(): void
    {
        Carbon::setTestNow('2026-06-24 08:05:00');

        $photo = 'data:image/jpeg;base64,' . base64_encode(str_repeat('x', 100));

        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.attendance.check-in.store'), [
                'punch_type' => 'in',
                'latitude'   => 23.8104000,
                'longitude'  => 90.4126000,
                'photo'      => $photo,
            ])
            ->assertRedirect(route('employee.dashboard'));

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', '2026-06-24')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->check_in);
        $this->assertNull($log->check_out);
        $this->assertSame(1, $log->punch_count);
        $checkInTime = $log->check_in->copy();

        Carbon::setTestNow('2026-06-24 17:05:00');

        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.attendance.check-in.store'), [
                'punch_type' => 'out',
                'latitude'   => 23.8104000,
                'longitude'  => 90.4126000,
            ])
            ->assertRedirect(route('employee.dashboard'));

        $log->refresh();

        $this->assertSame($checkInTime->format('H:i:s'), $log->check_in->format('H:i:s'));
        $this->assertNotNull($log->check_out);
        $this->assertSame('17:05:00', $log->check_out->format('H:i:s'));
        $this->assertSame(2, $log->punch_count);
        $this->assertGreaterThan(0, $log->work_minutes);
    }

    public function test_short_mobile_shift_still_records_work_minutes_after_check_out(): void
    {
        Carbon::setTestNow('2026-06-24 15:23:00');

        $photo = 'data:image/jpeg;base64,' . base64_encode(str_repeat('x', 100));

        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.attendance.check-in.store'), [
                'punch_type' => 'in',
                'latitude'   => 23.8104000,
                'longitude'  => 90.4126000,
                'photo'      => $photo,
            ])
            ->assertRedirect(route('employee.dashboard'));

        Carbon::setTestNow('2026-06-24 15:52:00');

        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.attendance.check-in.store'), [
                'punch_type' => 'out',
                'latitude'   => 23.8104000,
                'longitude'  => 90.4126000,
            ])
            ->assertRedirect(route('employee.dashboard'));

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', '2026-06-24')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('15:23:00', $log->check_in->format('H:i:s'));
        $this->assertSame('15:52:00', $log->check_out->format('H:i:s'));
        $this->assertSame(29, $log->work_minutes);
        $this->assertSame('0h 29m', $log->workHoursFormatted());

        Carbon::setTestNow();
    }

    public function test_qr_gate_check_in(): void
    {
        $gate = AttendanceGatePoint::create([
            'factory_id' => $this->factory->id,
            'code'       => 'GATE-01',
            'name'       => 'Main Gate',
            'latitude'   => 23.8103000,
            'longitude'  => 90.4125000,
            'qr_token'   => 'test-gate-token-123',
            'is_active'  => true,
        ]);

        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.attendance.check-in.store'), [
                'punch_type' => 'in',
                'latitude'   => 23.8103100,
                'longitude'  => 90.4125100,
                'gate'       => $gate->qr_token,
            ])
            ->assertRedirect(route('employee.dashboard'));

        $this->assertDatabaseHas('hrm_attendance_raw_punches', [
            'employee_id'  => $this->employee->id,
            'source'       => 'qr_scan',
            'gate_point_id'=> $gate->id,
        ]);
    }

    public function test_hr_manual_punch_creates_attendance(): void
    {
        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.attendance.manual-punch.store'), [
                'employee_id'     => $this->employee->id,
                'attendance_date' => '2026-06-19',
                'punch_time'      => '08:00',
                'punch_type'      => 'in',
                'reason'          => 'Device was offline',
            ])
            ->assertRedirect(route('admin.hrm.attendance.manual-punch.index'));

        $this->assertDatabaseHas('hrm_attendance_raw_punches', [
            'source'      => 'manual_hr',
            'employee_id' => $this->employee->id,
        ]);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', '2026-06-19')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->check_in);
    }

    public function test_hr_can_edit_manual_punch_and_recalculate_attendance(): void
    {
        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.attendance.manual-punch.store'), [
                'employee_id'     => $this->employee->id,
                'attendance_date' => '2026-06-19',
                'punch_time'      => '08:00',
                'punch_type'      => 'in',
                'reason'          => 'Device was offline',
            ]);

        $punch = AttendanceRawPunch::where('employee_id', $this->employee->id)
            ->where('source', 'manual_hr')
            ->first();

        $this->assertNotNull($punch);

        $this->actingAs($this->hrUser)
            ->put(route('admin.hrm.attendance.manual-punch.update', $punch), [
                'employee_id'     => $this->employee->id,
                'attendance_date' => '2026-06-19',
                'punch_time'      => '09:30',
                'punch_type'      => 'in',
                'reason'          => 'Corrected time',
            ])
            ->assertRedirect(route('admin.hrm.attendance.manual-punch.index'));

        $punch->refresh();

        $this->assertSame('09:30:00', $punch->punched_at->format('H:i:s'));
        $this->assertSame('Corrected time', $punch->reason);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', '2026-06-19')
            ->first();

        $this->assertSame('09:30:00', $log->check_in->format('H:i:s'));
    }

    public function test_iclock_getrequest_returns_ok(): void
    {
        $this->get('/iclock/getrequest?SN=SFV5L-TEST-001')
            ->assertOk()
            ->assertSee('OK');
    }
}
