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
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryGradeDetail;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\PayrollProcessor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollProcessingTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Factory $factory;

    private Employee $employee;

    private EmployeePortalUser $portalUser;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-20 12:00:00');

        $this->factory = Factory::create(['name' => 'Payroll Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Payroll Admin',
            'permissions' => ['hrm.salary.view', 'hrm.salary.manage', 'hrm.salary.approve'],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Payroll Admin',
            'email'    => 'hr-payroll-admin@test.com',
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
            'employee_code' => 'PR-W001',
            'name'          => 'Payroll Worker',
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
            'daily_wage'     => 500,
            'hra'            => 1000,
            'medical'        => 500,
            'conveyance'     => 0,
            'other_allowance'=> 0,
            'payment_method' => 'bank',
            'bank_account'   => '1234567890',
            'is_active'      => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedFrozenAttendance(): AttendancePeriod
    {
        $period = AttendancePeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        $period->update(['status' => 'frozen', 'frozen_at' => now()]);

        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => '2026-06-02',
            'status'          => 'present',
        ]);

        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => '2026-06-03',
            'status'          => 'present',
        ]);

        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => '2026-06-04',
            'status'          => 'late',
        ]);

        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'attendance_date' => '2026-06-05',
            'status'          => 'absent',
        ]);

        return $period;
    }

    public function test_processor_calculates_wages_from_frozen_attendance(): void
    {
        $this->seedFrozenAttendance();

        $payrollPeriod = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);

        $run = app(PayrollProcessor::class)->calculatePeriod($payrollPeriod, $this->hrUser);

        $this->assertSame('completed', $run->status);
        $this->assertSame(1, $run->employee_count);

        $item = PayrollItem::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($item);
        $this->assertSame('wages', $item->pay_type);
        $this->assertSame(3, $item->present_days);
        $this->assertSame(1, $item->absent_days);
        $this->assertSame(1, $item->late_days);

        // 3 paid days × 500 + pro-rata allowances (1500/26 × 3)
        $expectedGross = round(3 * 500 + (1500 / 26) * 3, 2);
        $this->assertEqualsWithDelta($expectedGross, (float) $item->gross_pay, 0.01);

        // absent: 1 × 500; single late day is within 3-day grace — no late deduction
        $expectedNet = max(0, round($expectedGross - 500, 2));
        $this->assertEqualsWithDelta(0.0, (float) $item->late_deduction, 0.01);
        $this->assertEqualsWithDelta($expectedNet, (float) $item->net_pay, 0.01);

        $payrollPeriod->refresh();
        $this->assertSame('calculated', $payrollPeriod->status);
    }

    public function test_processor_uses_gross_salary_for_staff(): void
    {
        $this->seedFrozenAttendance();

        $heads = collect(['GROSS', 'BASIC', 'HOUSE RENT'])->mapWithKeys(fn ($code) => [
            $code => \App\Models\Hrm\SalaryHead::create([
                'factory_id' => $this->factory->id,
                'code'       => $code,
                'name'       => $code,
                'head_type'  => 'E',
                'sort_order' => 1,
                'is_active'  => true,
            ])->id,
        ]);

        $grade = SalaryGrade::create([
            'factory_id' => $this->factory->id,
            'code'       => 'SR-STAFF',
            'name'       => 'Staff Grade',
            'is_active'  => true,
        ]);

        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['GROSS'], 'detail_type' => 'F', 'is_fixed' => false, 'amount' => 0]);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['BASIC'], 'detail_type' => 'M', 'is_fixed' => false, 'formula' => '<GROSS>/1.4']);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['HOUSE RENT'], 'detail_type' => 'P', 'is_fixed' => false, 'percentage' => 40, 'percentage_of_head_id' => $heads['BASIC']]);

        $staff = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $this->employee->shift_id,
            'employee_code' => 'PR-S002',
            'name'          => 'Gross Staff',
            'status'        => 'active',
        ]);

        $structure = SalaryStructure::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $staff->id,
            'salary_grade_id' => $grade->id,
            'pay_type'        => 'salary',
            'is_active'       => true,
            'payment_method'  => 'bank',
        ]);

        $amounts = app(\App\Services\Hrm\SalaryFormulaCalculator::class)->calculate($grade, 28000);
        $structure->syncLegacyFromHeads($amounts);
        $structure->save();

        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $staff->id,
            'attendance_date' => '2026-06-02',
            'status'          => 'present',
        ]);
        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $staff->id,
            'attendance_date' => '2026-06-03',
            'status'          => 'present',
        ]);
        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $staff->id,
            'attendance_date' => '2026-06-04',
            'status'          => 'late',
        ]);
        AttendanceDailyLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $staff->id,
            'attendance_date' => '2026-06-05',
            'status'          => 'absent',
        ]);

        $payrollPeriod = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        app(PayrollProcessor::class)->calculatePeriod($payrollPeriod, $this->hrUser);

        $item = PayrollItem::where('employee_id', $staff->id)->first();
        $this->assertNotNull($item);
        $this->assertSame('salary', $item->pay_type);
        $this->assertEqualsWithDelta(28000, (float) $item->gross_pay, 0.01);

        // 1 absent day × (BASIC / 26); single late within grace — no late deduction
        $basic = $amounts['BASIC'];
        $expectedNet = max(0, round(28000 - ($basic / 26), 2));
        $this->assertEqualsWithDelta(0.0, (float) $item->late_deduction, 0.01);
        $this->assertEqualsWithDelta($expectedNet, (float) $item->net_pay, 0.02);
    }

    public function test_calculate_fails_without_frozen_attendance(): void
    {
        AttendancePeriod::getOrCreateForMonth($this->factory->id, 2026, 6);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.salary.process.run'), [
                'factory_id' => $this->factory->id,
                'year'       => 2026,
                'month'      => 6,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('attendance');
    }

    public function test_hr_can_calculate_and_freeze_payroll(): void
    {
        $this->seedFrozenAttendance();

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.salary.process.run'), [
                'factory_id' => $this->factory->id,
                'year'       => 2026,
                'month'      => 6,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $period = PayrollPeriod::first();
        $this->assertNotNull($period);
        $this->assertSame('calculated', $period->status);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.salary.close.freeze', $period))
            ->assertRedirect()
            ->assertSessionHas('success');

        $period->refresh();
        $this->assertSame('frozen', $period->status);
    }

    public function test_employee_can_view_frozen_payslip(): void
    {
        $this->seedFrozenAttendance();

        $period = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        app(PayrollProcessor::class)->calculatePeriod($period, $this->hrUser);
        app(PayrollProcessor::class)->freezePeriod($period->fresh(), $this->hrUser);

        $item = PayrollItem::where('employee_id', $this->employee->id)->first();

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.payslips'))
            ->assertOk()
            ->assertSee('June 2026')
            ->assertSee(number_format((float) $item->net_pay, 2));

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.payslips.show', $item))
            ->assertOk()
            ->assertSee('Payroll Worker')
            ->assertSee('Net Pay')
            ->assertSee('Attendance Summary')
            ->assertSee('Employee Details')
            ->assertSee('Save PDF');

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.payslips.print', $item))
            ->assertOk()
            ->assertSee('Salary Payslip')
            ->assertSee('Save as PDF / Print');
    }

    public function test_hr_can_view_payslip_detail_from_period(): void
    {
        $this->seedFrozenAttendance();

        $period = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        app(PayrollProcessor::class)->calculatePeriod($period, $this->hrUser);
        app(PayrollProcessor::class)->freezePeriod($period->fresh(), $this->hrUser);

        $item = PayrollItem::where('employee_id', $this->employee->id)->first();

        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.salary.process.payslip', [$period, $item]))
            ->assertOk()
            ->assertSee('Payroll Worker')
            ->assertSee('Earnings')
            ->assertSee('Deductions')
            ->assertSee('Print View')
            ->assertSee('Save PDF')
            ->assertSee(number_format((float) $item->net_pay, 2));

        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.salary.process.payslip.print', [$period, $item]))
            ->assertOk()
            ->assertSee('Salary Payslip')
            ->assertSee('Save as PDF / Print');
    }

    public function test_hr_can_create_salary_structure(): void
    {
        $grade = SalaryGrade::create([
            'factory_id'  => $this->factory->id,
            'code'        => 'TEST-G1',
            'name'        => 'Test Grade',
            'is_active'   => true,
        ]);

        $other = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $this->employee->shift_id,
            'employee_code' => 'PR-S001',
            'name'          => 'Salaried Staff',
            'status'        => 'active',
        ]);

        $bank = SalaryBank::create([
            'factory_id' => $this->factory->id,
            'code'       => 'BRAC',
            'name'       => 'BRAC Bank PLC',
            'short_name' => 'BRAC Bank',
            'is_active'  => true,
        ]);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.salary.employee-salary.store'), [
                'employee_id'     => $other->id,
                'salary_grade_id' => $grade->id,
                'gross_salary'    => 25000,
                'payment_method'  => 'bank',
                'salary_bank_id'  => $bank->id,
                'bank_account'    => '9876543210',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('hrm_salary_structures', [
            'employee_id'  => $other->id,
            'gross_salary' => 25000,
            'pay_type'     => 'salary',
        ]);
    }

    public function test_bank_advise_export_requires_frozen_period(): void
    {
        $this->seedFrozenAttendance();

        $period = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        app(PayrollProcessor::class)->calculatePeriod($period, $this->hrUser);
        app(PayrollProcessor::class)->freezePeriod($period->fresh(), $this->hrUser);

        $this->actingAs($this->hrUser)
            ->get(route('admin.hrm.salary.close.bank-advise', $period))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
