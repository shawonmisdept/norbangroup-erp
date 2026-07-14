<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendancePunchLogsSelfieTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Factory $factory;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->factory = Factory::create(['name' => 'Punch Selfie Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Punch Viewer',
            'permissions' => ['hrm.attendance.view'],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Punch Viewer',
            'email'    => 'hr-punch-viewer@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->employee = Employee::create([
            'factory_id'        => $this->factory->id,
            'employee_code'     => 'PS-001',
            'name'              => 'Punch Selfie Worker',
            'biometric_user_id' => '801',
            'status'            => 'active',
        ]);
    }

    public function test_punch_logs_show_selfie_between_time_and_employee(): void
    {
        $photoPath = 'attendance-photos/PS-001/20260714_090200.jpg';
        Storage::disk('public')->put($photoPath, 'fake-image');

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
            ->get(route('admin.hrm.attendance.punches'));

        $response->assertOk()
            ->assertSee('>Image<', false)
            ->assertSee('storage/' . $photoPath, false)
            ->assertSee('Punch selfie for Punch Selfie Worker', false);

        $content = $response->getContent();
        $timePos = strpos($content, '>Time<');
        $imagePos = strpos($content, '>Image<');
        $employeePos = strpos($content, '>Employee<');

        $this->assertNotFalse($timePos);
        $this->assertNotFalse($imagePos);
        $this->assertNotFalse($employeePos);
        $this->assertLessThan($imagePos, $timePos);
        $this->assertLessThan($employeePos, $imagePos);
    }

    public function test_punch_logs_show_placeholder_when_no_selfie(): void
    {
        AttendanceRawPunch::create([
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => $this->employee->employee_code,
            'punched_at'        => now()->setTime(9, 0),
            'punch_type'        => 'in',
            'source'            => 'adms_push',
        ]);

        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.attendance.punches'))
            ->assertOk()
            ->assertSee('>Image<', false)
            ->assertDontSee('storage/attendance-photos/', false);
    }
}
