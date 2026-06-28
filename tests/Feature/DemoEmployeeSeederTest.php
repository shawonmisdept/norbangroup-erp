<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use Database\Seeders\Hrm\DemoEmployeeSeeder;
use Database\Seeders\Hrm\HrmMasterDataSeeder;
use Database\Seeders\Hrm\SalaryLegacySeeder;
use Database\Seeders\Masters\FactoryDesignationSeeder;
use Database\Seeders\Masters\FactorySeeder;
use Database\Seeders\Masters\UnitDepartmentsDesignationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoEmployeeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_and_restores_soft_deleted_employees(): void
    {
        $this->seed([
            FactorySeeder::class,
            HrmMasterDataSeeder::class,
            UnitDepartmentsDesignationsSeeder::class,
            FactoryDesignationSeeder::class,
            SalaryLegacySeeder::class,
        ]);

        $factory = Factory::where('name', 'Norban Comtex Limited')->first();
        $this->assertNotNull($factory);

        Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'NCL-D002',
            'name'          => 'Soft Deleted Worker',
            'status'        => 'active',
        ])->delete();

        $this->seed(DemoEmployeeSeeder::class);
        $this->seed(DemoEmployeeSeeder::class);

        $employee = Employee::where('employee_code', 'NCL-D002')->first();

        $this->assertNotNull($employee);
        $this->assertNull($employee->deleted_at);
        $this->assertSame('Fatema Begum', $employee->name);
        $this->assertSame(1, Employee::withTrashed()->where('employee_code', 'NCL-D002')->count());
    }
}
