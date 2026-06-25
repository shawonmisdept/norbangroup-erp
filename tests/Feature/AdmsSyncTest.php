<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\BiometricDevice;
use App\Models\Hrm\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdmsSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Factory $factory;

    private BiometricDevice $device;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        config(['hrm.adms.api_token' => 'test-api-token']);
        config(['hrm.adms.push_token' => 'test-push-token']);

        $this->factory = Factory::create(['name' => 'Test Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Attendance',
            'permissions' => ['hrm.attendance.view', 'hrm.attendance.sync'],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR',
            'email'    => 'hr-att@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->device = BiometricDevice::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Gate Device',
            'device_serial' => 'ZK-1001',
            'adms_url'      => 'https://adms.test',
            'is_active'     => true,
        ]);

        $this->employee = Employee::create([
            'factory_id'        => $this->factory->id,
            'employee_code'     => $this->factory->code . '-00001',
            'name'              => 'Mapped Worker',
            'biometric_user_id' => '101',
            'status'            => 'active',
        ]);
    }

    public function test_hr_can_view_attendance_hub(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.attendance.sync.index'))
            ->assertOk()
            ->assertSee('SpeedFace V5L');
    }

    public function test_adms_pull_sync_imports_punches(): void
    {
        Http::fake([
            'adms.test/*' => Http::response([
                'records' => [
                    [
                        'id'         => 'punch-1',
                        'user_id'    => '101',
                        'punch_time' => '2026-06-22 08:05:00',
                        'punch_state'=> '0',
                    ],
                    [
                        'id'         => 'punch-2',
                        'user_id'    => '999',
                        'punch_time' => '2026-06-22 08:06:00',
                        'punch_state'=> '1',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.attendance.devices.sync', $this->device))
            ->assertRedirect();

        $this->assertDatabaseCount('hrm_attendance_raw_punches', 2);
        $this->assertDatabaseHas('hrm_attendance_raw_punches', [
            'employee_id'       => $this->employee->id,
            'biometric_user_id' => '101',
            'punch_type'        => 'in',
            'source'            => 'adms_pull',
        ]);
        $this->assertDatabaseHas('hrm_attendance_raw_punches', [
            'employee_id'       => null,
            'biometric_user_id' => '999',
        ]);

        $this->device->refresh();
        $this->assertSame('success', $this->device->last_sync_status);
    }

    public function test_adms_push_endpoint_imports_punches(): void
    {
        $this->postJson(route('api.hrm.adms.push'), [
            'device_serial' => 'ZK-1001',
            'records' => [
                [
                    'id'         => 'push-1',
                    'user_id'    => '101',
                    'punch_time' => '2026-06-22 17:05:00',
                    'punch_state'=> '1',
                ],
            ],
        ], [
            'Authorization' => 'Bearer test-push-token',
        ])
            ->assertOk()
            ->assertJsonPath('records_imported', 1);

        $this->assertDatabaseHas('hrm_attendance_raw_punches', [
            'external_id' => 'push-1',
            'source'      => 'adms_push',
            'punch_type'  => 'out',
        ]);
    }

    public function test_adms_push_rejects_invalid_token(): void
    {
        $this->postJson(route('api.hrm.adms.push'), [
            'device_serial' => 'ZK-1001',
            'records'       => [],
        ], [
            'Authorization' => 'Bearer wrong-token',
        ])->assertUnauthorized();
    }

    public function test_duplicate_punches_are_skipped_on_resync(): void
    {
        Http::fake([
            'adms.test/*' => Http::response([
                'records' => [
                    [
                        'id'         => 'dup-1',
                        'user_id'    => '101',
                        'punch_time' => '2026-06-22 08:05:00',
                        'punch_state'=> '0',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.attendance.devices.sync', $this->device));

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.attendance.devices.sync', $this->device));

        $this->assertSame(1, AttendanceRawPunch::count());
    }
}
