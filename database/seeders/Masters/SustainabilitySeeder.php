<?php

namespace Database\Seeders\Masters;

use App\Models\Sustainability;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class SustainabilitySeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return Sustainability::class;
    }

    protected function records(): array
    {
        return $this->recordsFromDataFile('sustainabilities.php');
    }
}
