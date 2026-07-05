<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryGradeDetail;
use App\Models\Hrm\SalaryHead;
use App\Models\Hrm\SalaryStructure;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSalaryGradeTest extends TestCase
{
    use RefreshDatabase;

    private User $hrAdmin;

    private Factory $factory;

    private SalaryGrade $grade;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name'        => 'HR Admin',
            'permissions' => ['hrm.employees.view', 'hrm.employees.manage'],
        ]);

        $this->hrAdmin = User::create([
            'name'     => 'HR Admin',
            'email'    => 'hr-grade-admin@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->factory = Factory::create(['name' => 'Grade Factory', 'is_active' => true]);

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
            'code'       => 'G5',
            'name'       => 'Grade 5',
            'is_active'  => true,
        ]);

        SalaryGradeDetail::create(['salary_grade_id' => $this->grade->id, 'salary_head_id' => $heads['GROSS'], 'detail_type' => 'F', 'is_fixed' => false, 'amount' => 0]);
        SalaryGradeDetail::create(['salary_grade_id' => $this->grade->id, 'salary_head_id' => $heads['BASIC'], 'detail_type' => 'M', 'is_fixed' => false, 'formula' => '<GROSS>/1.4']);
        SalaryGradeDetail::create(['salary_grade_id' => $this->grade->id, 'salary_head_id' => $heads['HOUSE RENT'], 'detail_type' => 'P', 'is_fixed' => false, 'percentage' => 40, 'percentage_of_head_id' => $heads['BASIC']]);
    }

    public function test_employee_enrollment_assigns_salary_grade_only(): void
    {
        $this->actingAs($this->hrAdmin)
            ->post(route('admin.hrm.employees.store'), [
                'factory_id'      => $this->factory->id,
                'employee_code'   => 'G5-W001',
                'name'            => 'Grade Five Worker',
                'status'          => 'active',
                'salary_grade_id' => $this->grade->id,
            ])
            ->assertRedirect();

        $employee = Employee::where('employee_code', 'G5-W001')->first();
        $this->assertNotNull($employee);

        $structure = SalaryStructure::where('employee_id', $employee->id)->first();
        $this->assertNotNull($structure);
        $this->assertSame($this->grade->id, $structure->salary_grade_id);
        $this->assertEqualsWithDelta(0, (float) $structure->gross_salary, 0.01);
        $this->assertEqualsWithDelta(0, (float) $structure->daily_wage, 0.01);
    }

    public function test_employee_update_changes_salary_grade(): void
    {
        $employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'G5-W002',
            'name'          => 'Worker Two',
            'status'        => 'active',
        ]);

        SalaryStructure::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $employee->id,
            'salary_grade_id' => $this->grade->id,
        ]);

        $otherGrade = SalaryGrade::create([
            'factory_id' => $this->factory->id,
            'code'       => 'G4',
            'name'       => 'Grade 4',
            'is_active'  => true,
        ]);

        $this->actingAs($this->hrAdmin)
            ->put(route('admin.hrm.employees.update', $employee), [
                'factory_id'      => $this->factory->id,
                'employee_code'   => 'G5-W002',
                'name'            => 'Worker Two',
                'status'          => 'active',
                'salary_grade_id' => $otherGrade->id,
            ])
            ->assertRedirect();

        $structure = SalaryStructure::where('employee_id', $employee->id)->first();
        $this->assertNotNull($structure);
        $this->assertSame($otherGrade->id, $structure->salary_grade_id);
    }

    public function test_grade_must_belong_to_employee_factory(): void
    {
        $otherFactory = Factory::create(['name' => 'Other Factory', 'is_active' => true]);
        $foreignGrade = SalaryGrade::create([
            'factory_id' => $otherFactory->id,
            'code'       => 'X1',
            'name'       => 'Foreign Grade',
            'is_active'  => true,
        ]);

        $this->actingAs($this->hrAdmin)
            ->post(route('admin.hrm.employees.store'), [
                'factory_id'      => $this->factory->id,
                'employee_code'   => 'G5-W003',
                'name'            => 'Worker Three',
                'status'          => 'active',
                'salary_grade_id' => $foreignGrade->id,
            ])
            ->assertSessionHasErrors('salary_grade_id');
    }
}
