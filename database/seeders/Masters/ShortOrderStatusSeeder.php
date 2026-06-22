<?php

namespace Database\Seeders\Masters;

use App\Models\ShortOrderStatus;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class ShortOrderStatusSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return ShortOrderStatus::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Open'],
            ['name' => 'Under Review'],
            ['name' => 'Approved'],
            ['name' => 'Rejected'],
            ['name' => 'Closed'],
        ];
    }
}
