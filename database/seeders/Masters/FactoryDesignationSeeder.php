<?php

namespace Database\Seeders\Masters;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use Illuminate\Database\Seeder;

class FactoryDesignationSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('seeders/data/norban_factory_designations.php');

        $factory = Factory::where('name', $data['factory'])->where('is_active', true)->first();

        if (! $factory) {
            $this->command?->warn("Factory \"{$data['factory']}\" not found — skipping designation seed.");

            return;
        }

        $seeded = 0;

        foreach ($data['departments'] as $departmentName => $designations) {
            $department = Department::updateOrCreate(
                ['name' => $departmentName, 'factory_id' => $factory->id],
                ['is_active' => true]
            );

            foreach ($designations as $designationName) {
                Designation::updateOrCreate(
                    ['name' => $designationName, 'department_id' => $department->id],
                    ['is_active' => true]
                );

                $seeded++;
            }
        }

        $this->command?->info("Seeded {$seeded} designations for {$factory->name}.");
    }
}
