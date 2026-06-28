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
        $data = require database_path('seeders/data/factory_departments_designations.php');

        $departmentCount = 0;
        $designationCount = 0;

        foreach ($data as $factoryName => $departments) {
            $factory = Factory::query()->where('name', $factoryName)->where('is_active', true)->first();

            if (! $factory) {
                $this->command?->warn("Factory \"{$factoryName}\" not found — skipping factory designations.");

                continue;
            }

            foreach ($departments as $departmentName => $designationNames) {
                $department = Department::updateOrCreate(
                    [
                        'name'       => $departmentName,
                        'factory_id' => $factory->id,
                    ],
                    [
                        'is_active' => true,
                    ]
                );

                $departmentCount++;

                foreach ($designationNames as $designationName) {
                    Designation::updateOrCreate(
                        [
                            'name'          => $designationName,
                            'department_id' => $department->id,
                        ],
                        [
                            'is_active' => true,
                        ]
                    );

                    $designationCount++;
                }
            }
        }

        $this->command?->info(sprintf(
            'Seeded %d factory departments and %d department-linked designations.',
            $departmentCount,
            $designationCount
        ));
    }
}
