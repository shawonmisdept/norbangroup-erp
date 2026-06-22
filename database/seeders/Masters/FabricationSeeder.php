<?php

namespace Database\Seeders\Masters;

use App\Models\Fabrication;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class FabricationSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return Fabrication::class;
    }

    protected function records(): array
    {
        return [
            ['name' => 'Knitted'],
            ['name' => 'Woven'],
            ['name' => 'Non-Woven'],
            ['name' => 'Embroidered'],
        ];
    }
}
