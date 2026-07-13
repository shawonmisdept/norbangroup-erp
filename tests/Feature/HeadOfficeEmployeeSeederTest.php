<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\Employee;
use Database\Seeders\Hrm\HeadOfficeEmployeeSeeder;
use Database\Seeders\Masters\FactorySeeder;
use Database\Seeders\Masters\HeadOfficeOrgSeeder;
use Database\Seeders\Hrm\HrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeadOfficeEmployeeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_head_office_employee_seeder_imports_contact_list(): void
    {
        $this->seed([
            FactorySeeder::class,
            HrmMasterDataSeeder::class,
            \Database\Seeders\Masters\HeadOfficeOrgSeeder::class,
        ]);

        $factory = Factory::where('name', 'Head Office')->firstOrFail();
        $department = Department::where('factory_id', $factory->id)->firstOrFail();
        $designation = Designation::firstOrFail();

        Employee::create([
            'factory_id'     => $factory->id,
            'department_id'  => $department->id,
            'designation_id' => $designation->id,
            'employee_code'  => 'HO-OLD-1',
            'name'           => 'Old Employee',
            'status'         => 'active',
        ]);

        $this->seed(HeadOfficeEmployeeSeeder::class);

        $this->assertSame(121, Employee::where('factory_id', $factory->id)->count());
        $this->assertDatabaseMissing('hrm_employees', ['employee_code' => 'HO-OLD-1']);
        $this->assertDatabaseHas('hrm_employees', [
            'employee_code' => '3030',
            'name'          => 'L.C. Mohammad Abdul Bari (Rtd.)',
            'email'         => 'bari@norbangroup.com',
        ]);
        $this->assertDatabaseHas('hrm_employees', [
            'employee_code' => 'HO-M001',
            'name'          => 'Md. Wahidul Haque Siddiqui',
        ]);
        $this->assertTrue(
            Designation::query()->where('name', 'Managing Director (MD)')->exists()
        );
    }
}
