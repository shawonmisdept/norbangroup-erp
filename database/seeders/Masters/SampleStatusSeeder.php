<?php

namespace Database\Seeders\Masters;

use App\Models\SampleStatus;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class SampleStatusSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return SampleStatus::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Pending'],
            ['name' => 'In Progress'],
            ['name' => 'Submitted'],
            ['name' => 'Approved'],
            ['name' => 'Rejected'],
            ['name' => 'Re-Sample Required'],
        ];
    }
}
