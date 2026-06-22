<?php

namespace Database\Seeders\Masters;

use App\Models\ShipmentStatus;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class ShipmentStatusSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return ShipmentStatus::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Pending'],
            ['name' => 'Booked'],
            ['name' => 'In Transit'],
            ['name' => 'Delivered'],
            ['name' => 'Delayed'],
        ];
    }
}
