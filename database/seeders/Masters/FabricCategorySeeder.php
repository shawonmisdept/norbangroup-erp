<?php

namespace Database\Seeders\Masters;

use App\Models\FabricCategory;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class FabricCategorySeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return FabricCategory::class;
    }

    protected function records(): array
    {
        return [
            ['name' => 'Knit'],
            ['name' => 'Woven'],
            ['name' => 'Denim'],
            ['name' => 'Non-Woven'],
        ];
    }
}
