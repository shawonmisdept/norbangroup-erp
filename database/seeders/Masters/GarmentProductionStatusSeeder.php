<?php

namespace Database\Seeders\Masters;

use App\Models\GarmentProductionStatus;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class GarmentProductionStatusSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return GarmentProductionStatus::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Not Started'],
            ['name' => 'Cutting'],
            ['name' => 'Sewing'],
            ['name' => 'Finishing'],
            ['name' => 'Packed'],
            ['name' => 'Ready to Ship'],
        ];
    }
}
