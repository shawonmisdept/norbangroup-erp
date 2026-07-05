<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryStructure;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSalaryGradeFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name'        => 'Salary Admin',
            'permissions' => ['hrm.salary.view', 'hrm.salary.manage'],
        ]);

        $this->user = User::create([
            'name'     => 'Salary Admin',
            'email'    => 'salary-admin@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->factory = Factory::create(['name' => 'Filter Factory', 'is_active' => true]);

        $this->gradeA = SalaryGrade::create(['factory_id' => $this->factory->id, 'code' => 'G5', 'name' => 'Grade 5', 'is_active' => true]);
        $this->gradeB = SalaryGrade::create(['factory_id' => $this->factory->id, 'code' => 'G4', 'name' => 'Grade 4', 'is_active' => true]);
    }

    public function test_employee_salary_lists_only_employees_in_selected_grade(): void
    {
        $inGrade = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'G5-001',
            'name'          => 'In Grade Five',
            'status'        => 'active',
        ]);

        SalaryStructure::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $inGrade->id,
            'salary_grade_id' => $this->gradeA->id,
        ]);

        $otherGrade = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'G4-001',
            'name'          => 'In Grade Four',
            'status'        => 'active',
        ]);

        SalaryStructure::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $otherGrade->id,
            'salary_grade_id' => $this->gradeB->id,
        ]);

        $noGrade = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'NG-001',
            'name'          => 'No Grade Yet',
            'status'        => 'active',
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.hrm.salary.employee-salary.index', ['salary_grade_id' => $this->gradeA->id]))
            ->assertOk()
            ->assertSee('In Grade Five')
            ->assertDontSee('In Grade Four')
            ->assertDontSee('No Grade Yet');
    }
}
