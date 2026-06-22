<?php

namespace Database\Seeders\Masters;

use App\Models\MaterialType;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class MaterialTypeSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return MaterialType::class;
    }

    protected function records(): array
    {
        return [
            ['name' => 'Yarn'],
            ['name' => 'Fabric'],
            ['name' => 'Trims'],
            ['name' => 'Accessories'],
            ['name' => 'Packaging'],
        ];
    }
}
