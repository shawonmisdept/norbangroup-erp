<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Factory;
use Database\Seeders\Masters\DepartmentSeeder;
use Database\Seeders\Masters\FactoryDesignationSeeder;
use Database\Seeders\Masters\FactorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryDesignationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_no_op_until_unit_map_is_configured(): void
    {
        Factory::create([
            'name'      => 'Future Unit',
            'is_active' => true,
        ]);

        $this->seed(FactoryDesignationSeeder::class);

        $this->assertSame(0, Department::count());
    }

    public function test_department_seeder_skips_head_office_and_only_head_office_is_active(): void
    {
        $this->seed([
            FactorySeeder::class,
            DepartmentSeeder::class,
        ]);

        $headOffice = Factory::where('name', 'Head Office')->firstOrFail();

        $this->assertSame(0, Department::where('factory_id', $headOffice->id)->count());
        $this->assertSame(1, Factory::where('is_active', true)->count());
        $this->assertTrue((bool) $headOffice->is_active);
    }
}
