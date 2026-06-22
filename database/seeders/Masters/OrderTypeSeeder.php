<?php

namespace Database\Seeders\Masters;

use App\Models\OrderType;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class OrderTypeSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return OrderType::class;
    }

    protected function records(): array
    {
        return $this->recordsFromDataFile('order_types.php');
    }
}
