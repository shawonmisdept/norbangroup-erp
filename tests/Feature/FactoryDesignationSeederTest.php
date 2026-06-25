<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use Database\Seeders\Masters\FactoryDesignationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryDesignationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_full_designations_for_norban_factory(): void
    {
        Factory::create([
            'name'      => 'Norban Comtex Limited',
            'is_active' => true,
        ]);

        $this->seed(FactoryDesignationSeeder::class);

        $factory = Factory::where('name', 'Norban Comtex Limited')->first();

        $this->assertGreaterThanOrEqual(18, Department::where('factory_id', $factory->id)->count());
        $this->assertGreaterThanOrEqual(85, Designation::count());

        $sewing = Department::where('factory_id', $factory->id)->where('name', 'Sewing')->first();
        $this->assertNotNull($sewing);

        $this->assertDatabaseHas('designations', [
            'name'          => 'Line Chief',
            'department_id' => $sewing->id,
            'is_active'     => true,
        ]);

        $this->assertDatabaseHas('designations', [
            'name'          => 'HR Manager',
            'department_id' => Department::where('factory_id', $factory->id)->where('name', 'Human Resources')->value('id'),
        ]);
    }
}
