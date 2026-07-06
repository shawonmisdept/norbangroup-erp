<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceDailySummarySelfieTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Factory $factory;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->factory = Factory::create(['name' => 'Daily Summary Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Attendance Viewer',
            'permissions' => ['hrm.attendance.view'],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Viewer',
            'email'    => 'hr-viewer@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->employee = Employee::create([
            'factory_id'        => $this->factory->id,
            'employee_code'     => 'DS-001',
            'name'              => 'Selfie Worker',
            'biometric_user_id' => '901',
            'status'            => 'active',
        ]);
    }

    public function test_daily_summary_shows_check_in_selfie_before_date(): void
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $photoPath = 'attendance-photos/DS-001/20260706_090200.jpg';
        Storage::disk('public')->put($photoPath, 'fake-image');

        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => today(),
            'check_in'        => now()->setTime(9, 2),
            'status'          => 'present',
        ]);

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => $this->employee->employee_code,
            'punched_at'        => now()->setTime(9, 2),
            'punch_type'        => 'in',
            'source'            => 'mobile_gps',
            'photo_path'        => $photoPath,
        ]);

        $response = $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.attendance.daily'));

        $response->assertOk()
            ->assertSee('Selfie', false)
            ->assertSee('storage/' . $photoPath, false)
            ->assertSee('Check-in selfie for Selfie Worker', false);

        $content = $response->getContent();
        $selfiePos = strpos($content, 'Selfie');
        $datePos = strpos($content, '>Date<');

        $this->assertNotFalse($selfiePos);
        $this->assertNotFalse($datePos);
        $this->assertLessThan($datePos, $selfiePos);

        Carbon::setTestNow();
    }

    public function test_daily_summary_shows_placeholder_when_no_selfie(): void
    {
        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => today(),
            'check_in'        => now()->setTime(9, 0),
            'status'          => 'present',
        ]);

        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => $this->employee->employee_code,
            'punched_at'        => now()->setTime(9, 0),
            'punch_type'        => 'in',
            'source'            => 'adms_push',
        ]);

        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.attendance.daily'))
            ->assertOk()
            ->assertDontSee('storage/attendance-photos/', false);
    }
}
