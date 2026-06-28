<?php

namespace Database\Seeders\Masters;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use Illuminate\Database\Seeder;

class UnitDepartmentsDesignationsSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('seeders/data/unit_departments_designations.php');

        $factories = Factory::query()
            ->whereIn('name', $data['factories'])
            ->where('is_active', true)
            ->get()
            ->keyBy('name');

        if ($factories->isEmpty()) {
            $this->command?->warn('No target factories found — skipping unit department/designation seed.');

            return;
        }

        $missing = collect($data['factories'])->diff($factories->keys());

        if ($missing->isNotEmpty()) {
            $this->command?->warn('Missing factories: ' . $missing->implode(', '));
        }

        $factoryIds = $factories->pluck('id');
        $departmentIds = Department::whereIn('factory_id', $factoryIds)->pluck('id');

        Designation::whereIn('department_id', $departmentIds)->delete();
        Designation::whereNull('department_id')->delete();
        Department::whereIn('factory_id', $factoryIds)->delete();

        $departmentCount = 0;

        foreach ($data['factories'] as $factoryName) {
            $factory = $factories->get($factoryName);

            if (! $factory) {
                continue;
            }

            foreach ($data['departments'] as $departmentRow) {
                Department::updateOrCreate(
                    [
                        'name'       => $departmentRow['name'],
                        'factory_id' => $factory->id,
                    ],
                    [
                        'native_name' => $departmentRow['native_name'] ?: null,
                        'is_active'   => true,
                    ]
                );

                $departmentCount++;
            }
        }

        $designationCount = 0;

        foreach ($data['designations'] as $designationRow) {
            Designation::updateOrCreate(
                ['name' => $designationRow['name']],
                [
                    'native_name'   => $designationRow['native_name'] ?: null,
                    'department_id' => null,
                    'is_active'     => true,
                ]
            );

            $designationCount++;
        }

        $this->command?->info(sprintf(
            'Seeded %d departments across %d factories and %d designations from Excel master data.',
            $departmentCount,
            $factories->count(),
            $designationCount
        ));
    }
}
