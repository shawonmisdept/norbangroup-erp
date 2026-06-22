<?php

namespace Database\Seeders\Masters;

use App\Models\OrderStatus;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return OrderStatus::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Confirmed'],
            ['name' => 'In Production'],
            ['name' => 'Partial Shipment'],
            ['name' => 'Shipped'],
            ['name' => 'Completed'],
            ['name' => 'Cancelled'],
        ];
    }
}
