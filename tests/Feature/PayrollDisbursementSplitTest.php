<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryBank;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\DisbursementSplitService;
use App\Services\Hrm\PayrollProcessor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollDisbursementSplitTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $hrUser;

    private Employee $employee;

    private EmployeePortalUser $portalUser;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-25 10:00:00');

        $this->factory = Factory::create(['name' => 'Split Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Split',
            'permissions' => [
                'hrm.salary.view', 'hrm.salary.manage', 'hrm.salary.approve',
                'hrm.salary.close.view', 'hrm.salary.close.manage',
                'hrm.salary.process.view', 'hrm.salary.process.manage',
                'hrm.salary.employee-salary.view', 'hrm.salary.employee-salary.manage',
                'hrm.salary.banks.view', 'hrm.salary.banks.manage',
            ],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Split',
            'email'    => 'hr-split@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $shift = Shift::create([
            'factory_id' => $this->factory->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'SPLIT-1',
            'name'          => 'Split Worker',
            'email'         => 'split-worker@test.com',
            'status'        => 'active',
        ]);

        $this->portalUser = EmployeePortalUser::create([
            'employee_id' => $this->employee->id,
            'email'       => 'split-worker@test.com',
            'password'    => 'password',
            'is_active'   => true,
        ]);

        SalaryBank::create([
            'factory_id' => $this->factory->id,
            'code'       => 'SJIB',
            'name'       => 'Shahjalal Islami Bank PLC',
            'short_name' => 'Shahjalal Islami Bank',
            'is_active'  => true,
        ]);

        SalaryStructure::create([
            'factory_id'               => $this->factory->id,
            'employee_id'              => $this->employee->id,
            'pay_type'                 => 'wages',
            'daily_wage'               => 1000,
            'gross_salary'             => 26000,
            'basic_salary'             => 26000,
            'payment_method'           => 'split',
            'salary_bank_id'           => SalaryBank::first()->id,
            'bank_account'             => '1234567890',
            'bank_disbursement_amount' => 15000,
            'is_active'                => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedFrozenAttendance(): PayrollPeriod
    {
        $period = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        $attendancePeriod = AttendancePeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        $attendancePeriod->update(['status' => 'frozen', 'frozen_at' => now()]);

        for ($day = 1; $day <= 26; $day++) {
            AttendanceDailyLog::create([
                'factory_id'      => $this->factory->id,
                'employee_id'     => $this->employee->id,
                'attendance_date' => Carbon::create(2026, 6, $day)->toDateString(),
                'status'          => 'present',
                'work_minutes'    => 480,
            ]);
        }

        return $period;
    }

    public function test_split_payroll_calculates_bank_and_cash_amounts(): void
    {
        $period = $this->seedFrozenAttendance();

        app(PayrollProcessor::class)->calculatePeriod($period, $this->hrUser);

        $item = PayrollItem::where('employee_id', $this->employee->id)->firstOrFail();

        $net = (float) $item->net_pay;
        $this->assertGreaterThan(0, $net);
        $this->assertEqualsWithDelta(15000, (float) $item->bank_pay_amount, 0.01);
        $this->assertEqualsWithDelta($net - 15000, (float) $item->cash_pay_amount, 0.01);
        $this->assertEqualsWithDelta($net, (float) $item->bank_pay_amount + (float) $item->cash_pay_amount, 0.01);
    }

    public function test_freeze_blocked_until_cash_disbursed_marked(): void
    {
        $period = $this->seedFrozenAttendance();
        app(PayrollProcessor::class)->calculatePeriod($period, $this->hrUser);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(PayrollProcessor::class)->freezePeriod($period->fresh(), $this->hrUser);
    }

    public function test_accounts_can_override_split_and_close_after_cash_mark(): void
    {
        $period = $this->seedFrozenAttendance();
        app(PayrollProcessor::class)->calculatePeriod($period, $this->hrUser);
        $item = PayrollItem::where('employee_id', $this->employee->id)->firstOrFail();
        $net = (float) $item->net_pay;

        $this->actingAs($this->hrUser)
            ->put(route('admin.hrm.salary.disbursement.update-split', [$period, $item]), [
                'bank_pay_amount' => 16000,
                'cash_pay_amount' => round($net - 16000, 2),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $item->refresh();
        $this->assertTrue($item->disbursement_override);
        $this->assertEqualsWithDelta(16000, (float) $item->bank_pay_amount, 0.01);
        $this->assertEqualsWithDelta($net - 16000, (float) $item->cash_pay_amount, 0.01);

        app(DisbursementSplitService::class)->markCashDisbursed($item, $this->hrUser);

        app(PayrollProcessor::class)->freezePeriod($period->fresh(), $this->hrUser);

        $period->refresh();
        $this->assertSame('frozen', $period->status);
    }

    public function test_employee_cannot_view_payslip_before_close(): void
    {
        $period = $this->seedFrozenAttendance();
        app(PayrollProcessor::class)->calculatePeriod($period, $this->hrUser);
        $item = PayrollItem::where('employee_id', $this->employee->id)->firstOrFail();

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.payslips.show', $item))
            ->assertNotFound();
    }

    public function test_employee_can_view_payslip_after_close(): void
    {
        $period = $this->seedFrozenAttendance();
        app(PayrollProcessor::class)->calculatePeriod($period, $this->hrUser);
        $item = PayrollItem::where('employee_id', $this->employee->id)->firstOrFail();

        app(DisbursementSplitService::class)->markCashDisbursed($item, $this->hrUser);
        app(PayrollProcessor::class)->freezePeriod($period->fresh(), $this->hrUser);

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.payslips.show', $item))
            ->assertOk()
            ->assertSee('Net Pay')
            ->assertDontSee('Cash Pay');
    }
}
