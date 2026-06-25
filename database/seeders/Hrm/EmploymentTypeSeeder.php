<?php

namespace Database\Seeders\Hrm;

use App\Models\Hrm\EmploymentType;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class EmploymentTypeSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return EmploymentType::class;
    }

    protected function records(): array
    {
        return $this->recordsFromDataFile('hrm_employment_types.php');
    }
}
