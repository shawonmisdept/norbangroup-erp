<?php

namespace Database\Seeders\Masters;

use App\Models\Season;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return Season::class;
    }

    protected function records(): array
    {
        return [
            ['name' => 'SS 2026', 'year' => 2026, 'start_date' => '2026-01-01', 'end_date' => '2026-06-30'],
            ['name' => 'AW 2026', 'year' => 2026, 'start_date' => '2026-07-01', 'end_date' => '2026-12-31'],
            ['name' => 'SS 2027', 'year' => 2027, 'start_date' => '2027-01-01', 'end_date' => '2027-06-30'],
        ];
    }
}
