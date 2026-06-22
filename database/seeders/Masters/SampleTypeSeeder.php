<?php

namespace Database\Seeders\Masters;

use App\Models\SampleType;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class SampleTypeSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return SampleType::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Proto Sample'],
            ['name' => 'Fit Sample'],
            ['name' => 'Size Set Sample'],
            ['name' => 'PP Sample'],
            ['name' => 'TOP Sample'],
        ];
    }
}
