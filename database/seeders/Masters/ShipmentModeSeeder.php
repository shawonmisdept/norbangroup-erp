<?php

namespace Database\Seeders\Masters;

use App\Models\ShipmentMode;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class ShipmentModeSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return ShipmentMode::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Sea'],
            ['name' => 'Air'],
            ['name' => 'Road'],
            ['name' => 'Courier'],
        ];
    }
}
