<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\BonusRun;
use App\Models\Hrm\Employee;
use App\Models\Hrm\GratuitySettlement;
use App\Models\Hrm\MaternityRule;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\WorkerCategory;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\FestivalBonusCalculator;
use App\Services\Hrm\GratuityCalculator;
use App\Services\Hrm\MinimumWageValidator;
use App\Services\Hrm\OtCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HrmPriority3ComplianceTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(array $permissions = ['hrm.compliance.view', 'hrm.compliance.manage', 'hrm.leave.manage']): User
    {
        $role = Role::create(['name' => 'Compliance Admin', 'permissions' => $permissions]);

        return User::create([
            'name'     => 'Compliance Admin',
            'email'    => 'compliance@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    public function test_minimum_wage_validator_rejects_below_category_floor(): void
    {
        $factory = Factory::create(['name' => 'Wage Factory', 'is_active' => true]);
        $category = WorkerCategory::create([
            'code'          => 'WCT1',
            'name'          => 'Operator',
            'minimum_wage'  => 500,
            'is_active'     => true,
        ]);
        $employee = Employee::create([
            'factory_id'         => $factory->id,
            'worker_category_id' => $category->id,
            'employee_code'      => 'W-001',
            'name'               => 'Worker One',
            'status'             => 'active',
        ]);

        $this->expectException(ValidationException::class);
        app(MinimumWageValidator::class)->validateDailyWage($employee, 400);
    }

    public function test_ot_calculator_applies_holiday_multiplier(): void
    {
        $factory = Factory::create(['name' => 'OT Factory', 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'        => $factory->id,
            'employee_code'     => 'OT-001',
            'name'              => 'OT Worker',
            'status'            => 'active',
            'weekend_ot_allowed'=> true,
            'weekend_days'      => [6],
        ]);

        $policy = AttendancePolicy::forFactory($factory->id);
        $policy->update([
            'ot_multiplier_normal'  => 2.0,
            'ot_multiplier_holiday' => 3.0,
            'full_day_minutes'      => 480,
        ]);

        $log = AttendanceDailyLog::create([
            'factory_id'      => $factory->id,
            'employee_id'     => $employee->id,
            'attendance_date' => Carbon::parse('2026-06-06'),
            'status'          => 'present',
            'work_minutes'    => 600,
        ]);

        $result = app(OtCalculator::class)->calculate($employee, collect([$log]), $policy, 62.5);

        $this->assertSame(10.0, $result['breakdown']['holiday_hours']);
        $this->assertSame(1875.0, $result['ot_amount']);
    }

    public function test_gratuity_calculated_on_separation_after_five_years(): void
    {
        $factory = Factory::create(['name' => 'Grat Factory', 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'G-001',
            'name'          => 'Senior Worker',
            'status'        => 'resigned',
            'joining_date'  => now()->subYears(6)->toDateString(),
        ]);
        SalaryStructure::create([
            'factory_id'   => $factory->id,
            'employee_id'  => $employee->id,
            'pay_type'     => 'wages',
            'daily_wage'   => 500,
            'basic_salary' => 0,
            'is_active'    => true,
            'payment_method' => 'bank',
        ]);

        $user = $this->adminUser();
        $settlement = app(GratuityCalculator::class)->calculateOnSeparation($employee, $user);

        $this->assertNotNull($settlement);
        $this->assertGreaterThan(0, (float) $settlement->gratuity_amount);
        $this->assertDatabaseHas('hrm_gratuity_settlements', ['employee_id' => $employee->id]);
    }

    public function test_festival_bonus_run_calculates_items(): void
    {
        $factory = Factory::create(['name' => 'Bonus Factory', 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'B-001',
            'name'          => 'Bonus Worker',
            'status'        => 'active',
            'joining_date'  => now()->subYear()->toDateString(),
        ]);
        SalaryStructure::create([
            'factory_id'     => $factory->id,
            'employee_id'    => $employee->id,
            'pay_type'       => 'wages',
            'daily_wage'     => 600,
            'basic_salary'   => 0,
            'is_active'      => true,
            'payment_method' => 'bank',
        ]);

        $run = BonusRun::create([
            'factory_id' => $factory->id,
            'bonus_type' => 'eid_ul_fitr',
            'year'       => now()->year,
            'status'     => 'draft',
        ]);

        app(FestivalBonusCalculator::class)->calculate($run, $this->adminUser());

        $this->assertDatabaseHas('hrm_bonus_items', ['bonus_run_id' => $run->id, 'employee_id' => $employee->id]);
        $run->refresh();
        $this->assertSame('calculated', $run->status);
    }

    public function test_bonus_run_can_be_approved_after_calculate(): void
    {
        $factory = Factory::create(['name' => 'Approve Factory', 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'BA-001',
            'name'          => 'Approve Worker',
            'status'        => 'active',
            'joining_date'  => now()->subYear()->toDateString(),
        ]);
        SalaryStructure::create([
            'factory_id'     => $factory->id,
            'employee_id'    => $employee->id,
            'pay_type'       => 'wages',
            'daily_wage'     => 600,
            'basic_salary'   => 0,
            'is_active'      => true,
            'payment_method' => 'bank',
        ]);

        $run = BonusRun::create([
            'factory_id' => $factory->id,
            'bonus_type' => 'eid_ul_fitr',
            'year'       => now()->year,
            'status'     => 'draft',
        ]);

        $user = $this->adminUser();
        app(FestivalBonusCalculator::class)->calculate($run, $user);

        $this->actingAs($user)
            ->post(route('admin.hrm.compliance.bonus.approve', $run))
            ->assertRedirect(route('admin.hrm.compliance.bonus.show', $run));

        $this->assertSame('approved', $run->fresh()->status);
    }

    public function test_bonus_export_requires_approval(): void
    {
        $factory = Factory::create(['name' => 'Export Factory', 'is_active' => true]);
        $run = BonusRun::create([
            'factory_id' => $factory->id,
            'bonus_type' => 'eid_ul_fitr',
            'year'       => now()->year,
            'status'     => 'calculated',
        ]);

        $this->actingAs($this->adminUser())
            ->get(route('admin.hrm.compliance.bonus.export', $run))
            ->assertForbidden();
    }

    public function test_statutory_register_export_returns_csv(): void
    {
        $factory = Factory::create(['name' => 'Register Factory', 'is_active' => true]);

        $this->actingAs($this->adminUser())
            ->get(route('admin.hrm.compliance.registers.export', 'attendance', [
                'factory_id' => $factory->id,
                'year'       => now()->year,
                'month'      => now()->month,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_employee_show_displays_gratuity_settlement_link(): void
    {
        $factory = Factory::create(['name' => 'Show Grat Factory', 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'SG-001',
            'name'          => 'Separated Worker',
            'status'        => 'resigned',
            'joining_date'  => now()->subYears(6)->toDateString(),
        ]);
        SalaryStructure::create([
            'factory_id'     => $factory->id,
            'employee_id'    => $employee->id,
            'pay_type'       => 'wages',
            'daily_wage'     => 500,
            'basic_salary'   => 0,
            'is_active'      => true,
            'payment_method' => 'bank',
        ]);

        $user = $this->adminUser(['hrm.compliance.view', 'hrm.compliance.manage', 'hrm.employees.view']);
        app(GratuityCalculator::class)->calculateOnSeparation($employee, $user);

        $this->actingAs($user)
            ->get(route('admin.hrm.employees.show', $employee))
            ->assertOk()
            ->assertSee('Gratuity Settlement')
            ->assertSee('View Settlement');
    }

    public function test_age_verification_rejects_underage_employee(): void
    {
        $factory = Factory::create(['name' => 'Age Factory', 'is_active' => true]);
        AttendancePolicy::forFactory($factory->id)->update(['min_employment_age' => 18]);

        $role = Role::create(['name' => 'HR Admin', 'permissions' => ['hrm.employees.manage']]);
        $user = User::create([
            'name'       => 'HR',
            'email'      => 'hr-age@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);

        $this->actingAs($user)->post(route('admin.hrm.employees.store'), [
            'factory_id'    => $factory->id,
            'employee_code' => 'MINOR-1',
            'name'          => 'Too Young',
            'status'        => 'active',
            'date_of_birth' => now()->subYears(16)->toDateString(),
            'gender'        => 'male',
        ])->assertSessionHasErrors('date_of_birth');
    }

    public function test_compliance_hub_loads_for_compliance_permission(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('admin.hrm.compliance.hub'))
            ->assertOk()
            ->assertSee('Bangladesh Compliance');
    }

    public function test_maternity_transaction_requires_female_employee(): void
    {
        $factory = Factory::create(['name' => 'Mat Factory', 'is_active' => true]);
        MaternityRule::create([
            'factory_id'       => $factory->id,
            'total_weeks'      => 16,
            'paid_weeks'       => 8,
            'unpaid_weeks'     => 8,
            'min_service_days' => 0,
            'is_active'        => true,
        ]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'M-001',
            'name'          => 'Male Worker',
            'gender'        => 'male',
            'status'        => 'active',
        ]);

        $this->actingAs($this->adminUser())->post(route('admin.hrm.leave.maternity-transactions.store'), [
            'employee_id' => $employee->id,
            'start_date'  => now()->toDateString(),
            'end_date'    => now()->addWeeks(16)->toDateString(),
        ])->assertSessionHasErrors();
    }
}
