<?php

namespace Database\Seeders\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\Building;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmploymentType;
use App\Models\Hrm\Floor;
use App\Models\Hrm\Shift;
use App\Models\Hrm\WorkerCategory;
use Illuminate\Database\Seeder;

class UnitEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array{factory: string, employees: list<array<string, mixed>>}> $groups */
        $groups = require database_path('seeders/data/unit_employees.php');

        $employmentTypes = EmploymentType::query()->where('is_active', true)->pluck('id', 'name');
        $workerCategories = WorkerCategory::query()->where('is_active', true)->pluck('id', 'name');

        $seeded = 0;
        $skipped = 0;

        foreach ($groups as $group) {
            $factory = Factory::query()
                ->where('name', $group['factory'])
                ->where('is_active', true)
                ->first();

            if (! $factory) {
                $this->command?->warn("Factory \"{$group['factory']}\" not found — skipping unit employees.");

                continue;
            }

            $dayShiftId = Shift::query()
                ->where('factory_id', $factory->id)
                ->where('name', config('hrm.employee_defaults.shift_name', 'Day Shift'))
                ->where('is_active', true)
                ->value('id')
                ?? Shift::query()->where('factory_id', $factory->id)->where('is_active', true)->value('id');

            foreach ($group['employees'] as $row) {
                $department = Department::query()
                    ->where('factory_id', $factory->id)
                    ->where('name', $row['department'])
                    ->first();

                if (! $department) {
                    $this->command?->warn("Department \"{$row['department']}\" not found on {$factory->name} — employee {$row['employee_code']} skipped.");
                    $skipped++;

                    continue;
                }

                $designation = Designation::query()
                    ->where('name', $row['designation'])
                    ->where('department_id', $department->id)
                    ->first();

                if (! $designation) {
                    $this->command?->warn("Designation \"{$row['designation']}\" not found for {$row['department']} — employee {$row['employee_code']} skipped.");
                    $skipped++;

                    continue;
                }

                $buildingId = null;
                $floorId = null;

                if (! empty($row['building'])) {
                    $building = Building::query()
                        ->where('factory_id', $factory->id)
                        ->where('name', $row['building'])
                        ->first();

                    $buildingId = $building?->id;

                    if ($building && ! empty($row['floor'])) {
                        $floorId = Floor::query()
                            ->where('building_id', $building->id)
                            ->where('name', $row['floor'])
                            ->value('id');
                    }
                }

                $employmentTypeName = $row['employment_type'] ?? 'Permanent';
                $workerCategoryName = $row['worker_category'] ?? 'Staff';

                Employee::withTrashed()->updateOrCreate(
                    ['employee_code' => $row['employee_code']],
                    [
                        'factory_id'         => $factory->id,
                        'department_id'      => $department->id,
                        'designation_id'     => $designation->id,
                        'building_id'        => $buildingId,
                        'floor_id'           => $floorId,
                        'employment_type_id' => $employmentTypes[$employmentTypeName] ?? null,
                        'worker_category_id' => $workerCategories[$workerCategoryName] ?? null,
                        'shift_id'           => $dayShiftId,
                        'name'               => $row['name'],
                        'phone'              => $row['phone'] ?? null,
                        'email'              => $row['email'] ?? null,
                        'joining_date'       => $row['joining_date'] ?? null,
                        'status'             => $row['status'] ?? 'active',
                        'deleted_at'         => null,
                    ]
                );

                $seeded++;
            }
        }

        $this->command?->info("Seeded {$seeded} unit employee(s)." . ($skipped ? " Skipped {$skipped}." : ''));
    }
}
