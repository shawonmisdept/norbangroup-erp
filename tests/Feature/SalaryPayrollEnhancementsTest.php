<?php

namespace Tests\Feature;

use App\Mail\PayslipReadyMail;
use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\Employee;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryGradeDetail;
use App\Models\Hrm\SalaryHead;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\PayrollProcessor;
use App\Services\Hrm\SalaryFormulaCalculator;
use App\Services\Hrm\SalaryIncrementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SalaryPayrollEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $hrUser;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-20 12:00:00');

        $this->factory = Factory::create(['name' => 'Enhance Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Enhance',
            'permissions' => ['hrm.salary.view', 'hrm.salary.manage', 'hrm.salary.approve'],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Enhance',
            'email'    => 'hr-enhance@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_wages_payroll_includes_overtime_from_work_minutes(): void
    {
        $shift = Shift::create([
            'factory_id' => $this->factory->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);

        $employee = Employee::create([
            'factory_id' => $this->factory->id, 'shift_id' => $shift->id,
            'employee_code' => 'OT001', 'name' => 'OT Worker', 'status' => 'active',
        ]);

        SalaryStructure::create([
            'factory_id' => $this->factory->id, 'employee_id' => $employee->id,
            'pay_type' => 'wages', 'daily_wage' => 800, 'is_active' => true, 'payment_method' => 'bank',
        ]);

        $attendancePeriod = AttendancePeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        $attendancePeriod->update(['status' => 'frozen', 'frozen_at' => now()]);

        AttendanceDailyLog::create([
            'factory_id' => $this->factory->id, 'employee_id' => $employee->id,
            'attendance_date' => '2026-06-02', 'status' => 'present', 'work_minutes' => 600,
        ]);

        $period = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        app(PayrollProcessor::class)->calculatePeriod($period, $this->hrUser);

        $item = PayrollItem::where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);
        $this->assertEqualsWithDelta(2.0, (float) $item->ot_hours, 0.01);
        $this->assertGreaterThan(0, (float) $item->ot_amount);
        $this->assertEqualsWithDelta(800 + (2 * (800 / 8) * 2), (float) $item->gross_pay, 0.01);
    }

    public function test_staff_payroll_applies_deduction_heads(): void
    {
        $shift = Shift::create([
            'factory_id' => $this->factory->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);

        SalaryHead::create(['factory_id' => $this->factory->id, 'code' => 'GROSS', 'name' => 'Gross', 'head_type' => 'E', 'is_active' => true]);
        SalaryHead::create(['factory_id' => $this->factory->id, 'code' => 'BASIC', 'name' => 'Basic', 'head_type' => 'E', 'is_active' => true]);
        SalaryHead::create(['factory_id' => $this->factory->id, 'code' => 'STAMP', 'name' => 'Stamp', 'head_type' => 'D', 'is_active' => true]);

        $grade = SalaryGrade::create(['factory_id' => $this->factory->id, 'code' => 'G1', 'name' => 'G1', 'is_active' => true]);

        $employee = Employee::create([
            'factory_id' => $this->factory->id, 'shift_id' => $shift->id,
            'employee_code' => 'ST001', 'name' => 'Staff', 'status' => 'active',
        ]);

        $structure = SalaryStructure::create([
            'factory_id' => $this->factory->id, 'employee_id' => $employee->id,
            'salary_grade_id' => $grade->id, 'pay_type' => 'salary', 'gross_salary' => 20000,
            'is_active' => true, 'payment_method' => 'bank',
        ]);
        $structure->syncLegacyFromHeads(['GROSS' => 20000, 'BASIC' => 15000, 'STAMP' => 20]);
        $structure->save();

        $attendancePeriod = AttendancePeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        $attendancePeriod->update(['status' => 'frozen', 'frozen_at' => now()]);

        AttendanceDailyLog::create([
            'factory_id' => $this->factory->id, 'employee_id' => $employee->id,
            'attendance_date' => '2026-06-02', 'status' => 'present',
        ]);

        $period = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        app(PayrollProcessor::class)->calculatePeriod($period, $this->hrUser);

        $item = PayrollItem::where('employee_id', $employee->id)->first();
        $this->assertEqualsWithDelta(20, (float) $item->other_deduction, 0.01);
        $this->assertEqualsWithDelta(19980, (float) $item->net_pay, 0.01);
    }

    public function test_increment_upload_csv_sets_new_gross(): void
    {
        $heads = collect(['GROSS', 'BASIC'])->mapWithKeys(fn ($code) => [
            $code => SalaryHead::create([
                'factory_id' => $this->factory->id, 'code' => $code, 'name' => $code,
                'head_type' => 'E', 'is_active' => true,
            ])->id,
        ]);

        $grade = SalaryGrade::create(['factory_id' => $this->factory->id, 'code' => 'SR-01', 'name' => 'SR-01', 'is_active' => true]);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['GROSS'], 'detail_type' => 'F', 'is_fixed' => false, 'amount' => 0]);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['BASIC'], 'detail_type' => 'M', 'is_fixed' => false, 'formula' => '<GROSS>/1.4']);

        $shift = Shift::create([
            'factory_id' => $this->factory->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);

        $employee = Employee::create([
            'factory_id' => $this->factory->id, 'shift_id' => $shift->id,
            'employee_code' => 'UPL001', 'name' => 'Upload Staff', 'status' => 'active',
        ]);

        $structure = SalaryStructure::create([
            'factory_id' => $this->factory->id, 'employee_id' => $employee->id,
            'salary_grade_id' => $grade->id, 'gross_salary' => 28000, 'pay_type' => 'salary',
            'is_active' => true, 'payment_method' => 'bank',
        ]);
        $structure->syncLegacyFromHeads(app(SalaryFormulaCalculator::class)->calculate($grade, 28000));
        $structure->save();

        $csv = "employee_code,new_gross,rule_name\nUPL001,30000,\n";
        $file = UploadedFile::fake()->createWithContent('inc.csv', $csv);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.salary.increment-upload.store'), [
                'factory_id' => $this->factory->id,
                'file'       => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEqualsWithDelta(30000, (float) $structure->fresh()->gross_salary, 0.01);
    }

    public function test_freeze_dispatches_payslip_emails(): void
    {
        Mail::fake();

        $shift = Shift::create([
            'factory_id' => $this->factory->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);

        $employee = Employee::create([
            'factory_id' => $this->factory->id, 'shift_id' => $shift->id,
            'employee_code' => 'EM001', 'name' => 'Email Staff', 'email' => 'staff@test.com', 'status' => 'active',
        ]);

        SalaryStructure::create([
            'factory_id' => $this->factory->id, 'employee_id' => $employee->id,
            'pay_type' => 'wages', 'daily_wage' => 500, 'is_active' => true, 'payment_method' => 'bank',
        ]);

        $attendancePeriod = AttendancePeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        $attendancePeriod->update(['status' => 'frozen', 'frozen_at' => now()]);

        AttendanceDailyLog::create([
            'factory_id' => $this->factory->id, 'employee_id' => $employee->id,
            'attendance_date' => '2026-06-02', 'status' => 'present',
        ]);

        $period = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        app(PayrollProcessor::class)->calculatePeriod($period, $this->hrUser);

        $this->actingAs($this->hrUser)
            ->post(route('admin.hrm.salary.close.freeze', $period), ['send_payslips' => 1])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(PayslipReadyMail::class, fn ($mail) => $mail->hasTo('staff@test.com'));
    }
}
