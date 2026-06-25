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
        $photo = 'data:image/jpeg;base64,' . base64_encode(str_repeat('x', 100));

        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.attendance.check-in.store'), [
                'punch_type' => 'in',
                'latitude'   => 23.8104000,
                'longitude'  => 90.4126000,
                'photo'      => $photo,
            ])
            ->assertRedirect(route('employee.attendance'));

        $this->assertDatabaseHas('hrm_attendance_raw_punches', [
            'employee_id' => $this->employee->id,
            'source'      => 'mobile_gps',
            'punch_type'  => 'in',
        ]);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)
            ->whereDate('attendance_date', today())
            ->first();

        $this->assertNotNull($log);
        $this->assertContains($log->status, ['present', 'late']);
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
            ->assertRedirect(route('employee.attendance'));

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

    public function test_iclock_getrequest_returns_ok(): void
    {
        $this->get('/iclock/getrequest?SN=SFV5L-TEST-001')
            ->assertOk()
            ->assertSee('OK');
    }
}
