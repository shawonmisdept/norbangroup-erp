<?php

namespace Database\Seeders\Hrm;

use App\Models\Hrm\WorkerCategory;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class WorkerCategorySeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return WorkerCategory::class;
    }

    protected function records(): array
    {
        return $this->recordsFromDataFile('hrm_worker_categories.php');
    }
}
