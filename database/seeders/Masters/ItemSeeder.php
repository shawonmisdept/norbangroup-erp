<?php

namespace Database\Seeders\Masters;

use App\Models\Item;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return Item::class;
    }

    protected function records(): array
    {
        return $this->recordsFromDataFile('items.php');
    }
}
