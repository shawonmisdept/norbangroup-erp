<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\LateAcceptanceApplication;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\LateAcceptanceService;
use App\Services\Hrm\LateDeductionCalculator;
use App\Services\Hrm\PayrollProcessor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceLateAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Factory $factory;

    private Employee $employee;

    private EmployeePortalUser $portalUser;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-25 12:00:00');

        $this->factory = Factory::create(['name' => 'Late Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Attendance Full',
            'permissions' => [
                'hrm.attendance.view', 'hrm.attendance.manage', 'hrm.attendance.approve',
                'hrm.attendance.policy.view', 'hrm.attendance.late-acceptance.view',
                'hrm.salary.view', 'hrm.salary.manage',
            ],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Admin',
            'email'    => 'hr-late@test.com',
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
            'employee_code' => 'LT-W001',
            'name'          => 'Late Worker',
            'status'        => 'active',
        ]);

        $this->portalUser = EmployeePortalUser::create([
            'employee_id' => $this->employee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        SalaryStructure::create([
            'factory_id'     => $this->factory->id,
            'employee_id'    => $this->employee->id,
            'pay_type'       => 'wages',
            'daily_wage'     => 600,
            'payment_method' => 'cash',
            'is_active'      => true,
        ]);

        AttendancePolicy::forFactory($this->factory->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_fourth_consecutive_late_charges_one_day_wage(): void
    {
        foreach (['2026-06-02', '2026-06-03', '2026-06-04', '2026-06-05'] as $date) {
            AttendanceDailyLog::create([
                'factory_id'      => $this->factory->id,
                'employee_id'     => $this->employee->id,
                'attendance_date' => $date,
                'status'          => 'late',
            ]);
        }

        $structure = $this->employee->salaryStructure;
        $logs = AttendanceDailyLog::where('employee_id', $this->employee->id)->get();
        $result = app(LateDeductionCalculator::class)->calculate($this->employee, $structure, $logs);

        $this->assertSame(1, $result['charged_days']);
        $this->assertSame(600.0, $result['amount']);
        $this->assertSame(0, $result['forgiven_days']);
    }

    public function test_approved_late_acceptance_forgives_deduction(): void
    {
        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => '2026-06-10',
            'status'          => 'late',
        ]);

        $application = LateAcceptanceApplication::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => '2026-06-10',
            'reason'          => 'Transport strike',
            'status'          => 'pending',
            'applied_at'      => now(),
        ]);

        app(LateAcceptanceService::class)->approve($application, $this->hrUser);

        $application->refresh();
        $this->assertSame('approved', $application->status);

        $log = AttendanceDailyLog::where('employee_id', $this->employee->id)->first();
        $this->assertTrue($log->is_late_forgiven);

        $period = AttendancePeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        $period->update(['status' => 'frozen', 'frozen_at' => now()]);

        $payrollPeriod = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        app(PayrollProcessor::class)->calculatePeriod($payrollPeriod, $this->hrUser);

        $item = PayrollItem::where('employee_id', $this->employee->id)->first();
        $this->assertSame(0.0, (float) $item->late_deduction);
        $this->assertSame(1, $item->late_forgiven_days);
    }

    public function test_employee_can_apply_for_late_acceptance(): void
    {
        $this->employee->salaryStructure->update([
            'pay_type'     => 'monthly',
            'gross_salary' => 18000,
            'daily_wage'   => 0,
        ]);

        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => '2026-06-12',
            'status'          => 'late',
        ]);

        $this->actingAs($this->portalUser, 'employee')
            ->post(route('employee.late-acceptance.apply.store'), [
                'attendance_date' => '2026-06-12',
                'reason'          => 'Family emergency',
            ])
            ->assertRedirect(route('employee.late-acceptance.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('hrm_late_acceptance_applications', [
            'employee_id' => $this->employee->id,
            'status'      => 'pending',
        ]);
    }

    public function test_hr_can_view_attendance_hub_and_late_acceptance(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.attendance.hub'))
            ->assertOk()
            ->assertSee('Late Acceptance');

        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.attendance.late-acceptance.index'))
            ->assertOk()
            ->assertSee('Late Acceptance Applications');
    }
}
