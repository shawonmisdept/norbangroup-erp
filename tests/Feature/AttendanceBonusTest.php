<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmploymentType;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\AttendanceBonusCalculator;
use App\Services\Hrm\PayrollProcessor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceBonusTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Factory $factory;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-20 12:00:00');

        $this->factory = Factory::create(['name' => 'Bonus Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'HR Bonus Admin',
            'permissions' => ['hrm.salary.view', 'hrm.salary.manage', 'hrm.salary.approve'],
        ]);

        $this->hrUser = User::create([
            'name'     => 'HR Bonus Admin',
            'email'    => 'hr-bonus-admin@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Morning',
            'start_time'    => '10:00:00',
            'end_time'      => '18:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createWorker(array $employeeOverrides = [], array $structureOverrides = []): Employee
    {
        $employee = Employee::create(array_merge([
            'factory_id'               => $this->factory->id,
            'shift_id'                 => $this->shift->id,
            'employee_code'            => 'AB-' . uniqid(),
            'name'                     => 'Bonus Worker',
            'status'                   => 'active',
            'attendance_bonus_enabled' => true,
            'attendance_bonus_amount'  => 500,
        ], $employeeOverrides));

        SalaryStructure::create(array_merge([
            'factory_id'     => $this->factory->id,
            'employee_id'    => $employee->id,
            'pay_type'       => 'wages',
            'daily_wage'     => 500,
            'hra'            => 1000,
            'medical'        => 500,
            'conveyance'     => 0,
            'other_allowance'=> 0,
            'payment_method' => 'bank',
            'is_active'      => true,
        ], $structureOverrides));

        return $employee;
    }

    /** @param list<array{date: string, status: string}> $logs */
    private function seedAttendance(Employee $employee, array $logs): AttendancePeriod
    {
        $period = AttendancePeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        $period->update(['status' => 'frozen', 'frozen_at' => now()]);

        foreach ($logs as $log) {
            AttendanceDailyLog::create([
                'factory_id'      => $this->factory->id,
                'employee_id'     => $employee->id,
                'attendance_date' => $log['date'],
                'status'          => $log['status'],
            ]);
        }

        return $period;
    }

    private function runPayroll(): PayrollItem
    {
        $payrollPeriod = PayrollPeriod::getOrCreateForMonth($this->factory->id, 2026, 6);
        app(PayrollProcessor::class)->calculatePeriod($payrollPeriod, $this->hrUser);

        return PayrollItem::first();
    }

    public function test_attendance_bonus_paid_with_perfect_attendance(): void
    {
        $employee = $this->createWorker();

        $this->seedAttendance($employee, [
            ['date' => '2026-06-02', 'status' => 'present'],
            ['date' => '2026-06-03', 'status' => 'present'],
            ['date' => '2026-06-04', 'status' => 'late'],
        ]);

        $item = $this->runPayroll();

        $this->assertSame($employee->id, $item->employee_id);
        $this->assertEqualsWithDelta(500, (float) ($item->head_breakdown['earnings']['ATT_BONUS'] ?? 0), 0.01);
        $this->assertTrue($item->head_breakdown['attendance_bonus']['eligible']);

        $baseGross = round(3 * 500 + (1500 / 26) * 3, 2);
        $this->assertEqualsWithDelta($baseGross + 500, (float) $item->gross_pay, 0.01);
    }

    public function test_attendance_bonus_denied_with_absent_day(): void
    {
        $employee = $this->createWorker();

        $this->seedAttendance($employee, [
            ['date' => '2026-06-02', 'status' => 'present'],
            ['date' => '2026-06-03', 'status' => 'absent'],
        ]);

        $item = $this->runPayroll();

        $this->assertSame(1, $item->absent_days);
        $this->assertFalse($item->head_breakdown['attendance_bonus']['eligible']);
        $this->assertStringContainsString('Absent', $item->head_breakdown['attendance_bonus']['reason']);
        $this->assertArrayNotHasKey('ATT_BONUS', $item->head_breakdown['earnings'] ?? []);
    }

    public function test_attendance_bonus_denied_with_three_or_more_late_days(): void
    {
        $employee = $this->createWorker();

        $this->seedAttendance($employee, [
            ['date' => '2026-06-02', 'status' => 'late'],
            ['date' => '2026-06-03', 'status' => 'late'],
            ['date' => '2026-06-04', 'status' => 'late'],
        ]);

        $item = $this->runPayroll();

        $this->assertSame(3, $item->late_days);
        $this->assertFalse($item->head_breakdown['attendance_bonus']['eligible']);
        $this->assertStringContainsString('Late days', $item->head_breakdown['attendance_bonus']['reason']);
    }

    public function test_attendance_bonus_denied_for_probation_employee(): void
    {
        $employee = $this->createWorker(['status' => 'probation']);

        $this->seedAttendance($employee, [
            ['date' => '2026-06-02', 'status' => 'present'],
        ]);

        $item = $this->runPayroll();

        $this->assertFalse($item->head_breakdown['attendance_bonus']['eligible']);
        $this->assertStringContainsString('probation', strtolower($item->head_breakdown['attendance_bonus']['reason']));
    }

    public function test_attendance_bonus_denied_for_trainee(): void
    {
        $traineeType = EmploymentType::create(['name' => 'Trainee', 'is_active' => true]);

        $employee = $this->createWorker(['employment_type_id' => $traineeType->id]);

        $this->seedAttendance($employee, [
            ['date' => '2026-06-02', 'status' => 'present'],
        ]);

        $item = $this->runPayroll();

        $this->assertFalse($item->head_breakdown['attendance_bonus']['eligible']);
        $this->assertStringContainsString('Trainee', $item->head_breakdown['attendance_bonus']['reason']);
    }

    public function test_attendance_bonus_denied_with_leave_day(): void
    {
        $employee = $this->createWorker();

        $this->seedAttendance($employee, [
            ['date' => '2026-06-02', 'status' => 'present'],
            ['date' => '2026-06-03', 'status' => 'leave'],
        ]);

        $item = $this->runPayroll();

        $this->assertSame(1, $item->leave_days);
        $this->assertFalse($item->head_breakdown['attendance_bonus']['eligible']);
        $this->assertStringContainsString('Leave', $item->head_breakdown['attendance_bonus']['reason']);
    }

    public function test_calculator_unit_eligibility_rules(): void
    {
        $employee = Employee::make([
            'status'                   => 'active',
            'attendance_bonus_enabled' => true,
            'attendance_bonus_amount'  => 600,
        ]);

        $calculator = app(AttendanceBonusCalculator::class);

        $eligible = $calculator->calculate($employee, 0, 0, 0, 2);
        $this->assertTrue($eligible['eligible']);
        $this->assertEqualsWithDelta(600, $eligible['amount'], 0.01);

        $denied = $calculator->calculate($employee, 0, 0, 0, 3);
        $this->assertFalse($denied['eligible']);
        $this->assertSame(0.0, $denied['amount']);
    }

    public function test_payroll_item_reflects_employee_attendance_counts(): void
    {
        $employee = $this->createWorker(['attendance_bonus_enabled' => false]);

        $this->seedAttendance($employee, [
            ['date' => '2026-06-02', 'status' => 'present'],
            ['date' => '2026-06-03', 'status' => 'present'],
            ['date' => '2026-06-04', 'status' => 'late'],
            ['date' => '2026-06-05', 'status' => 'absent'],
        ]);

        $item = $this->runPayroll();

        $this->assertSame('wages', $item->pay_type);
        $this->assertSame(3, $item->present_days);
        $this->assertSame(1, $item->absent_days);
        $this->assertSame(1, $item->late_days);
        $this->assertSame(0, $item->leave_days);
        $this->assertEqualsWithDelta(500, (float) $item->basic_amount / 3, 0.01);
    }
}
