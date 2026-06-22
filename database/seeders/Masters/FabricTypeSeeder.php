<?php

namespace Database\Seeders\Masters;

use App\Models\FabricType;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class FabricTypeSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return FabricType::class;
    }

    protected function records(): array
    {
        return $this->recordsFromDataFile('fabric_types.php');
    }
}
