<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryGradeDetail;
use App\Models\Hrm\SalaryHead;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SalaryUploadTest extends TestCase
{
    use RefreshDatabase;

    private function seedGrade(Factory $factory): SalaryGrade
    {
        $heads = collect(['GROSS', 'BASIC', 'HOUSE RENT'])->mapWithKeys(fn ($code) => [
            $code => SalaryHead::create([
                'factory_id' => $factory->id,
                'code'       => $code,
                'name'       => $code,
                'head_type'  => 'E',
                'sort_order' => 1,
                'is_active'  => true,
            ])->id,
        ]);

        $grade = SalaryGrade::create([
            'factory_id' => $factory->id,
            'code'       => 'SR-01',
            'name'       => 'SR-01',
            'is_active'  => true,
        ]);

        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['GROSS'], 'detail_type' => 'F', 'is_fixed' => false, 'amount' => 0]);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['BASIC'], 'detail_type' => 'M', 'is_fixed' => false, 'formula' => '<GROSS>/1.4']);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $heads['HOUSE RENT'], 'detail_type' => 'P', 'is_fixed' => false, 'percentage' => 40, 'percentage_of_head_id' => $heads['BASIC']]);

        return $grade;
    }

    public function test_hr_can_upload_salary_csv_with_gross_and_grade(): void
    {
        $factory = Factory::create(['name' => 'Upload Factory', 'is_active' => true]);
        $this->seedGrade($factory);

        $role = Role::create([
            'name'        => 'Salary Upload Admin',
            'permissions' => ['hrm.salary.view', 'hrm.salary.manage'],
        ]);

        $user = User::create([
            'name'     => 'Upload Admin',
            'email'    => 'upload-admin@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $shift = Shift::create([
            'factory_id' => $factory->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);

        $employee = Employee::create([
            'factory_id' => $factory->id, 'shift_id' => $shift->id,
            'employee_code' => 'UPL001', 'name' => 'Upload Staff', 'status' => 'active',
        ]);

        $csv = "employee_code,pay_type,salary_grade_code,gross_salary,daily_wage,hra,medical,conveyance,other_allowance,payment_method,bank_account\n";
        $csv .= "UPL001,salary,SR-01,28000,0,0,0,0,0,bank,9988776655\n";

        $file = UploadedFile::fake()->createWithContent('salaries.csv', $csv);

        $this->actingAs($user)
            ->post(route('admin.hrm.salary.upload.store'), [
                'factory_id' => $factory->id,
                'file'       => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $structure = SalaryStructure::where('employee_id', $employee->id)->first();
        $this->assertNotNull($structure);
        $this->assertSame('salary', $structure->pay_type);
        $this->assertEquals(28000, (float) $structure->gross_salary);
        $this->assertNotNull($structure->head_amounts);
        $this->assertEquals(28000, (float) $structure->head_amounts['GROSS']);
    }

    public function test_hr_can_upload_wages_csv(): void
    {
        $factory = Factory::create(['name' => 'Wages Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Wages Upload Admin',
            'permissions' => ['hrm.salary.view', 'hrm.salary.manage'],
        ]);

        $user = User::create([
            'name'     => 'Wages Admin',
            'email'    => 'wages-admin@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $shift = Shift::create([
            'factory_id' => $factory->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);

        $employee = Employee::create([
            'factory_id' => $factory->id, 'shift_id' => $shift->id,
            'employee_code' => 'WPL001', 'name' => 'Upload Worker', 'status' => 'active',
        ]);

        $csv = "employee_code,pay_type,salary_grade_code,gross_salary,daily_wage,hra,medical,conveyance,other_allowance,payment_method,bank_account\n";
        $csv .= "WPL001,wages,,0,500,1000,500,0,0,bank,1122334455\n";

        $file = UploadedFile::fake()->createWithContent('wages.csv', $csv);

        $this->actingAs($user)
            ->post(route('admin.hrm.salary.upload.store'), [
                'factory_id' => $factory->id,
                'file'       => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('hrm_salary_structures', [
            'employee_id' => $employee->id,
            'pay_type'    => 'wages',
            'daily_wage'  => 500,
        ]);
    }
}
