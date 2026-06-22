<?php

namespace Database\Seeders\Masters;

use App\Models\ItemBodyPart;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class ItemBodyPartSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return ItemBodyPart::class;
    }

    protected function records(): array
    {
        return $this->recordsFromDataFile('item_body_parts.php');
    }
}
