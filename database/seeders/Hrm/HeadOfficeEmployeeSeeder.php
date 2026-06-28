<?php

namespace Database\Seeders\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmploymentType;
use App\Models\Hrm\WorkerCategory;
use Illuminate\Database\Seeder;

class HeadOfficeEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('seeders/data/head_office_employees.php');

        $factory = Factory::query()
            ->where('name', $data['factory'])
            ->where('is_active', true)
            ->first();

        if (! $factory) {
            $this->command?->warn("Factory \"{$data['factory']}\" not found — skipping Head Office employee seed.");

            return;
        }

        $this->deleteExistingEmployees($factory);

        $employmentType = EmploymentType::query()
            ->where('name', 'Permanent')
            ->where('is_active', true)
            ->first();

        $workerCategory = WorkerCategory::query()
            ->where('name', 'Staff')
            ->where('is_active', true)
            ->first();

        $seeded = 0;

        foreach ($data['employees'] as $row) {
            $department = Department::query()
                ->where('factory_id', $factory->id)
                ->where('name', $row['department'])
                ->first();

            if (! $department) {
                $this->command?->warn("Department \"{$row['department']}\" not found for {$factory->name} — employee {$row['employee_code']} skipped.");

                continue;
            }

            $designation = Designation::query()
                ->where('name', $row['designation'])
                ->where(function ($query) use ($department) {
                    $query->whereNull('department_id')
                        ->orWhere('department_id', $department->id);
                })
                ->first();

            if (! $designation) {
                $this->command?->warn("Designation \"{$row['designation']}\" not found — employee {$row['employee_code']} skipped.");

                continue;
            }

            Employee::updateOrCreate(
                ['employee_code' => $row['employee_code']],
                [
                    'factory_id'         => $factory->id,
                    'department_id'      => $department->id,
                    'designation_id'     => $designation->id,
                    'employment_type_id' => $employmentType?->id,
                    'worker_category_id' => $workerCategory?->id,
                    'name'               => $row['name'],
                    'phone'              => $row['phone'] ?? null,
                    'email'              => $row['email'] ?? null,
                    'nid_number'         => $row['nid_number'] ?? null,
                    'present_address'    => $row['present_address'] ?? null,
                    'permanent_address'  => $row['permanent_address'] ?? null,
                    'joining_date'       => $row['joining_date'] ?? null,
                    'status'             => $row['status'] ?? 'active',
                ]
            );

            $seeded++;
        }

        $this->command?->info("Seeded {$seeded} employees for {$factory->name}.");
    }

    private function deleteExistingEmployees(Factory $factory): void
    {
        $deleted = Employee::withTrashed()
            ->where('factory_id', $factory->id)
            ->count();

        Employee::withTrashed()
            ->where('factory_id', $factory->id)
            ->each(fn (Employee $employee) => $employee->forceDelete());

        if ($deleted > 0) {
            $this->command?->info("Removed {$deleted} existing {$factory->name} employee(s).");
        }
    }
}
