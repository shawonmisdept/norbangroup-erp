<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\Employee;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\LeaveBalance;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LeaveBulkEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Factory $factory;

    private Employee $employee;

    private LeaveType $casualLeave;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');

        $this->factory = Factory::create(['name' => 'Bulk Leave Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Leave Admin',
            'permissions' => ['hrm.leave.view', 'hrm.leave.manage', 'hrm.leave.approve'],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Leave Admin',
            'email'    => 'hr-bulk-leave@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Morning',
            'start_time'    => '10:00:00',
            'end_time'      => '18:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $this->employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'LV-B001',
            'name'          => 'Bulk Leave Worker',
            'status'        => 'active',
        ]);

        $this->casualLeave = LeaveType::create([
            'code'              => 'LVT-CL001',
            'name'              => 'Casual Leave (CL)',
            'is_paid'           => true,
            'max_days_per_year' => 10,
            'is_active'         => true,
        ]);

        LeaveBalance::create([
            'factory_id'    => $this->factory->id,
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'year'          => 2026,
            'entitled_days' => 10,
            'used_days'     => 0,
            'pending_days'  => 0,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_hr_can_view_bulk_entry_page(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.leave.bulk-entry.index'))
            ->assertOk()
            ->assertSee('Leave Entry Bulk')
            ->assertSee('Upload CSV');
    }

    public function test_hr_can_import_leave_via_csv(): void
    {
        $csv = implode("\n", [
            'employee_code,leave_type_code,start_date,end_date,reason',
            'LV-B001,LVT-CL001,2026-06-17,2026-06-18,Medical',
        ]);

        $file = UploadedFile::fake()->createWithContent('leaves.csv', $csv);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.leave.bulk-entry.store'), [
                'factory_id' => $this->factory->id,
                'file'       => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $application = LeaveApplication::first();
        $this->assertNotNull($application);
        $this->assertSame('approved', $application->status);
        $this->assertSame($this->employee->id, $application->employee_id);
        $this->assertSame(2.0, (float) $application->total_days);

        $balance = LeaveBalance::where('employee_id', $this->employee->id)->first();
        $this->assertSame(2.0, (float) $balance->used_days);

        $this->assertSame(2, AttendanceDailyLog::where('employee_id', $this->employee->id)->where('status', 'leave')->count());
    }

    public function test_bulk_import_skips_invalid_rows(): void
    {
        $csv = implode("\n", [
            'employee_code,leave_type_code,start_date,end_date,reason',
            'UNKNOWN,LVT-CL001,2026-06-17,2026-06-17,Test',
            'LV-B001,LVT-CL001,2026-06-19,2026-06-19,Valid',
        ]);

        $file = UploadedFile::fake()->createWithContent('leaves.csv', $csv);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.leave.bulk-entry.store'), [
                'factory_id' => $this->factory->id,
                'file'       => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('error');

        $this->assertSame(1, LeaveApplication::count());
    }
}
