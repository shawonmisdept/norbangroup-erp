<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryGradeDetail;
use App\Models\Hrm\SalaryHead;
use App\Models\Hrm\SalaryIncrementLog;
use App\Models\Hrm\SalaryIncrementRule;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\SalaryFormulaCalculator;
use App\Services\Hrm\SalaryIncrementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalaryIncrementTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private SalaryGrade $grade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create(['name' => 'Increment Factory', 'is_active' => true]);

        $heads = collect(['GROSS', 'BASIC', 'HOUSE RENT'])->mapWithKeys(fn ($code) => [
            $code => SalaryHead::create([
                'factory_id' => $this->factory->id,
                'code'       => $code,
                'name'       => $code,
                'head_type'  => 'E',
                'sort_order' => 1,
                'is_active'  => true,
            ])->id,
        ]);

        $this->grade = SalaryGrade::create([
            'factory_id' => $this->factory->id,
            'code'       => 'SR-01',
            'name'       => 'SR-01',
            'is_active'  => true,
        ]);

        SalaryGradeDetail::create(['salary_grade_id' => $this->grade->id, 'salary_head_id' => $heads['GROSS'], 'detail_type' => 'F', 'is_fixed' => false, 'amount' => 0]);
        SalaryGradeDetail::create(['salary_grade_id' => $this->grade->id, 'salary_head_id' => $heads['BASIC'], 'detail_type' => 'M', 'is_fixed' => false, 'formula' => '<GROSS>/1.4']);
        SalaryGradeDetail::create(['salary_grade_id' => $this->grade->id, 'salary_head_id' => $heads['HOUSE RENT'], 'detail_type' => 'P', 'is_fixed' => false, 'percentage' => 40, 'percentage_of_head_id' => $heads['BASIC']]);
    }

    public function test_increment_service_applies_percentage_to_gross(): void
    {
        $shift = Shift::create([
            'factory_id' => $this->factory->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);

        $employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'INC001',
            'name'          => 'Increment Staff',
            'joining_date'  => now()->subYears(2)->toDateString(),
            'status'        => 'active',
        ]);

        $amounts = app(SalaryFormulaCalculator::class)->calculate($this->grade, 28000);
        $structure = SalaryStructure::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $employee->id,
            'salary_grade_id' => $this->grade->id,
            'pay_type'        => 'salary',
            'is_active'       => true,
            'payment_method'  => 'bank',
        ]);
        $structure->gross_salary = 28000;
        $structure->syncLegacyFromHeads($amounts);
        $structure->save();

        $rule = SalaryIncrementRule::create([
            'factory_id'        => $this->factory->id,
            'salary_grade_id'   => $this->grade->id,
            'name'              => '5% Annual',
            'increment_type'    => 'percentage',
            'increment_value'   => 5,
            'min_tenure_months' => 12,
            'is_active'         => true,
        ]);

        $user = User::create([
            'name' => 'HR', 'email' => 'hr-inc@test.com', 'password' => 'password',
            'role_id' => Role::create(['name' => 'HR', 'permissions' => ['hrm.salary.manage']])->id,
        ]);

        app(SalaryIncrementService::class)->applyToEmployee($employee->fresh(['salaryStructure']), $rule, $user);

        $structure->refresh();
        $this->assertEqualsWithDelta(29400, (float) $structure->gross_salary, 0.01);
        $this->assertNotNull($structure->head_amounts);
        $this->assertEquals(29400, (float) $structure->head_amounts['GROSS']);

        $this->assertDatabaseHas('hrm_salary_increment_logs', [
            'employee_id'    => $employee->id,
            'previous_gross' => 28000,
            'new_gross'      => 29400,
        ]);
    }

    public function test_hr_can_access_increment_rules_index(): void
    {
        $role = Role::create(['name' => 'Salary HR', 'permissions' => ['hrm.salary.view', 'hrm.salary.manage']]);
        $user = User::create(['name' => 'HR', 'email' => 'hr-rules@test.com', 'password' => 'password', 'role_id' => $role->id]);

        SalaryIncrementRule::create([
            'factory_id' => $this->factory->id, 'name' => 'Test Rule',
            'increment_type' => 'fixed', 'increment_value' => 500, 'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.salary.increment-rules.index'))
            ->assertOk()
            ->assertSee('Test Rule');
    }

    public function test_bulk_apply_increments_selected_employees(): void
    {
        $role = Role::create(['name' => 'Bulk HR', 'permissions' => ['hrm.salary.view', 'hrm.salary.manage']]);
        $user = User::create(['name' => 'HR', 'email' => 'hr-bulk@test.com', 'password' => 'password', 'role_id' => $role->id]);

        $shift = Shift::create([
            'factory_id' => $this->factory->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);

        $employee = Employee::create([
            'factory_id' => $this->factory->id, 'shift_id' => $shift->id,
            'employee_code' => 'BLK001', 'name' => 'Bulk Staff',
            'joining_date' => now()->subYears(2)->toDateString(), 'status' => 'active',
        ]);

        $structure = SalaryStructure::create([
            'factory_id' => $this->factory->id, 'employee_id' => $employee->id,
            'salary_grade_id' => $this->grade->id, 'gross_salary' => 20000,
            'pay_type' => 'salary', 'is_active' => true, 'payment_method' => 'bank',
        ]);
        $structure->syncLegacyFromHeads(app(SalaryFormulaCalculator::class)->calculate($this->grade, 20000));
        $structure->save();

        $rule = SalaryIncrementRule::create([
            'factory_id' => $this->factory->id, 'salary_grade_id' => $this->grade->id,
            'name' => 'Fixed 1000', 'increment_type' => 'fixed', 'increment_value' => 1000,
            'min_tenure_months' => 0, 'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('admin.hrm.salary.increment-bulk.apply'), [
                'rule_id'      => $rule->id,
                'employee_ids' => [$employee->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEqualsWithDelta(21000, (float) $structure->fresh()->gross_salary, 0.01);
        $this->assertSame(1, SalaryIncrementLog::count());
    }
}
