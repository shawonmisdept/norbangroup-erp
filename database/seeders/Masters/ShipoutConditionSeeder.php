<?php

namespace Database\Seeders\Masters;

use App\Models\ShipoutCondition;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class ShipoutConditionSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return ShipoutCondition::class; }

    protected function records(): array
    {
        return [
            ['name' => 'FOB Chittagong'],
            ['name' => 'FOB Dhaka'],
            ['name' => 'CIF Destination'],
            ['name' => 'Ex-Factory'],
        ];
    }
}
