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
            ['name' => 'New'],
            ['name' => 'Under Review'],
            ['name' => 'Commercial Quote'],
            ['name' => 'Quoted'],
            ['name' => 'Approved'],
            ['name' => 'In Production'],
            ['name' => 'Shipped'],
            ['name' => 'Closed'],
            ['name' => 'Cancelled'],
        ];
    }
}
