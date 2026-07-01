<?php

namespace Database\Seeders\Masters;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\Building;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Floor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HeadOfficeOrgSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{factory: string, buildings: list<array{name: string, floors: list<array{name: string, floor_number: int|null}>}>, departments: list<array{name: string, native_name: ?string, default_building: ?string, default_floor: ?string, designations: list<array{name: string, role: ?string}>}>} $data */
        $data = require database_path('seeders/data/head_office_org.php');

        $factory = Factory::query()
            ->where('name', $data['factory'])
            ->where('is_active', true)
            ->first();

        if (! $factory) {
            $this->command?->warn("Factory \"{$data['factory']}\" not found — skipping Head Office org seed.");

            return;
        }

        DB::transaction(function () use ($factory, $data) {
            $this->wipeHeadOfficeOrg($factory);

            foreach ($data['buildings'] as $buildingRow) {
                $building = Building::create([
                    'factory_id'  => $factory->id,
                    'name'        => $buildingRow['name'],
                    'is_active'   => true,
                ]);

                foreach ($buildingRow['floors'] as $floorRow) {
                    Floor::create([
                        'factory_id'   => $factory->id,
                        'building_id'  => $building->id,
                        'name'         => $floorRow['name'],
                        'floor_number' => $floorRow['floor_number'],
                        'is_active'    => true,
                    ]);
                }
            }

            $departmentCount = 0;
            $designationCount = 0;

            foreach ($data['departments'] as $departmentRow) {
                $department = Department::create([
                    'factory_id'  => $factory->id,
                    'name'        => $departmentRow['name'],
                    'native_name' => $departmentRow['native_name'],
                    'is_active'   => true,
                ]);

                $departmentCount++;

                foreach ($departmentRow['designations'] as $designationRow) {
                    Designation::create([
                        'name'          => $designationRow['name'],
                        'department_id' => $department->id,
                        'is_active'     => true,
                    ]);

                    $designationCount++;
                }
            }

            $this->command?->info(sprintf(
                'Head Office org seeded: %d buildings, %d departments, %d designations.',
                count($data['buildings']),
                $departmentCount,
                $designationCount
            ));
        });
    }

    private function wipeHeadOfficeOrg(Factory $factory): void
    {
        $removedEmployees = Employee::withTrashed()
            ->where('factory_id', $factory->id)
            ->count();

        Employee::withTrashed()
            ->where('factory_id', $factory->id)
            ->each(fn (Employee $employee) => $employee->forceDelete());

        if ($removedEmployees > 0) {
            $this->command?->info("Removed {$removedEmployees} existing Head Office employee(s).");
        }

        $departmentIds = Department::query()
            ->where('factory_id', $factory->id)
            ->pluck('id');

        if ($departmentIds->isNotEmpty()) {
            Designation::query()->whereIn('department_id', $departmentIds)->delete();
            Department::query()->whereIn('id', $departmentIds)->delete();
        }

        Floor::query()->where('factory_id', $factory->id)->delete();
        Building::query()->where('factory_id', $factory->id)->delete();
    }
}
