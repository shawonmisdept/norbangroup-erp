<?php

namespace Database\Seeders\Masters;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use Illuminate\Database\Seeder;

class DepartmentDesignationFromEmployeeDataSeeder extends Seeder
{
    /** @var list<string> */
    private array $dataFiles = [
        'head_office_employees.php',
        'demo_employees.php',
    ];

    public function run(): void
    {
        $designationCount = 0;

        foreach ($this->dataFiles as $file) {
            $path = database_path('seeders/data/' . $file);

            if (! is_file($path)) {
                continue;
            }

            /** @var array{factory: string, employees: list<array{department?: string, designation?: string}>} $data */
            $data = require $path;

            $factory = Factory::query()
                ->where('name', $data['factory'])
                ->where('is_active', true)
                ->first();

            if (! $factory) {
                $this->command?->warn("Factory \"{$data['factory']}\" not found — skipping {$file}.");

                continue;
            }

            foreach ($data['employees'] as $row) {
                $departmentName = trim((string) ($row['department'] ?? ''));
                $designationName = trim((string) ($row['designation'] ?? ''));

                if ($departmentName === '' || $designationName === '') {
                    continue;
                }

                $department = Department::query()
                    ->where('factory_id', $factory->id)
                    ->where('name', $departmentName)
                    ->first();

                if (! $department) {
                    continue;
                }

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

        $this->command?->info("Linked {$designationCount} department designations from employee data.");
    }
}
