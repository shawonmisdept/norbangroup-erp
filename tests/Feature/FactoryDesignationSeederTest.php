<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\Employee;
use Database\Seeders\Hrm\HrmMasterDataSeeder;
use Database\Seeders\Hrm\UnitEmployeeSeeder;
use Database\Seeders\Masters\DepartmentSeeder;
use Database\Seeders\Masters\FactoryDesignationSeeder;
use Database\Seeders\Masters\FactorySeeder;
use Database\Seeders\Masters\HeadOfficeOrgSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryDesignationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_unit_departments_and_designations(): void
    {
        $this->seed([
            FactorySeeder::class,
            FactoryDesignationSeeder::class,
        ]);

        $ncl = Factory::where('name', 'Norban Comtex Limited')->firstOrFail();
        $this->assertDatabaseHas('departments', [
            'factory_id' => $ncl->id,
            'name'       => 'GARMENTS & TEXTILE',
        ]);
        $department = Department::where('factory_id', $ncl->id)->where('name', 'GARMENTS & TEXTILE')->firstOrFail();
        $this->assertDatabaseHas('designations', [
            'department_id' => $department->id,
            'name'          => 'Chief Operating Officer (COO)',
        ]);
    }

    public function test_department_seeder_skips_head_office(): void
    {
        $this->seed([
            FactorySeeder::class,
            DepartmentSeeder::class,
        ]);

        $headOffice = Factory::where('name', 'Head Office')->firstOrFail();

        $this->assertSame(0, Department::where('factory_id', $headOffice->id)->count());
        $this->assertSame(6, Factory::where('is_active', true)->count());
        $this->assertTrue((bool) $headOffice->is_active);
    }

    public function test_unit_employee_seeder_imports_management_list(): void
    {
        $this->seed([
            FactorySeeder::class,
            HrmMasterDataSeeder::class,
            DepartmentSeeder::class,
            FactoryDesignationSeeder::class,
            HeadOfficeOrgSeeder::class,
        ]);

        $this->seed(UnitEmployeeSeeder::class);

        $this->assertDatabaseHas('hrm_employees', [
            'employee_code' => 'NCL-M001',
            'name'          => 'Mr Sushanta Sarker',
        ]);
        $this->assertDatabaseHas('hrm_employees', [
            'employee_code' => 'HAL-M001',
            'name'          => 'Kamrunnahar',
        ]);
        $this->assertDatabaseHas('hrm_employees', [
            'employee_code' => 'FAH-M001',
            'name'          => 'Fiber @ Home',
        ]);
        $this->assertDatabaseHas('hrm_employees', [
            'employee_code' => 'DHL-M001',
            'name'          => 'Moynul Haque Sir (Res.)',
            'status'        => 'active',
        ]);
        $this->assertSame(10, Employee::whereIn('employee_code', [
            'NCL-M001', 'NCL-M002', 'NCL-M003', 'NCL-M004', 'NCL-M005', 'NCL-M006',
            'HAL-M001', 'HAL-M002', 'FAH-M001', 'DHL-M001',
        ])->count());
    }
}
