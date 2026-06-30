<?php

namespace Database\Seeders\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\JobPosting;
use App\Models\Hrm\WorkerCategory;
use Illuminate\Database\Seeder;

class DemoJobPostingSeeder extends Seeder
{
    public function run(): void
    {
        $rows = require database_path('seeders/data/demo_job_postings.php');
        $created = 0;

        foreach ($rows as $row) {
            $factory = Factory::query()
                ->where('name', $row['factory'])
                ->where('is_active', true)
                ->first();

            if (! $factory) {
                $this->command?->warn("Factory \"{$row['factory']}\" not found — skipping \"{$row['title']}\".");

                continue;
            }

            $department = null;
            if (! empty($row['department'])) {
                $department = Department::query()
                    ->where('factory_id', $factory->id)
                    ->where('name', $row['department'])
                    ->first();
            }

            $designation = null;
            if (! empty($row['designation'])) {
                $designationQuery = Designation::query()->where('name', $row['designation']);

                if ($department) {
                    $designation = (clone $designationQuery)->where('department_id', $department->id)->first();
                }

                $designation ??= $designationQuery
                    ->whereHas('department', fn ($q) => $q->where('factory_id', $factory->id))
                    ->first();
            }

            $workerCategory = null;
            if (! empty($row['worker_category'])) {
                $workerCategory = WorkerCategory::query()
                    ->where('name', $row['worker_category'])
                    ->where('is_active', true)
                    ->first();
            }

            $salaryNegotiable = (bool) ($row['salary_negotiable'] ?? false);
            $salaryText = $row['salary_text'] ?? null;
            if ($salaryText === 'Negotiable') {
                $salaryText = null;
                $salaryNegotiable = true;
            }

            JobPosting::query()->updateOrCreate(
                [
                    'factory_id' => $factory->id,
                    'title'      => $row['title'],
                ],
                [
                    'department_id'      => $department?->id,
                    'designation_id'     => $designation?->id,
                    'worker_category_id' => $workerCategory?->id,
                    'shift_type'         => $row['shift_type'] ?? null,
                    'min_age'            => $row['min_age'] ?? null,
                    'max_age'            => $row['max_age'] ?? null,
                    'slots'              => $row['slots'],
                    'openings_filled'   => 0,
                    'status'             => 'open',
                    'is_internal'        => false,
                    'salary_text'        => $salaryText,
                    'salary_negotiable'  => $salaryNegotiable,
                    'description'        => $row['description'] ?? null,
                    'description_bn'     => $row['description_bn'] ?? null,
                    'requirements'       => $row['requirements'] ?? null,
                    'responsibilities'   => $row['responsibilities'] ?? null,
                    'skills_expertise'   => $row['skills_expertise'] ?? null,
                    'employment_status'  => $row['employment_status'] ?? null,
                    'benefits'           => $row['benefits'] ?? null,
                    'meta_description'   => $row['meta_description'] ?? null,
                    'template_key'       => $row['template_key'] ?? null,
                    'published_at'       => now(),
                    'closes_at'          => now()->addDays(45)->startOfDay(),
                ]
            );

            $created++;
        }

        $operatorSlots = collect($rows)
            ->filter(fn ($r) => str_contains($r['title'], 'Sewing'))
            ->sum('slots');
        $supervisorSlots = collect($rows)
            ->filter(fn ($r) => str_contains($r['title'], 'Line Supervisor'))
            ->sum('slots');

        $this->command?->info(sprintf(
            'Seeded %d demo job posting(s) — %d open operator + %d supervisor slots across Norban Comtex & Hornbill.',
            $created,
            $operatorSlots,
            $supervisorSlots,
        ));
    }
}
