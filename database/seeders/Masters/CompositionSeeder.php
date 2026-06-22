<?php

namespace Database\Seeders\Masters;

use App\Models\Composition;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class CompositionSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return Composition::class;
    }

    protected function records(): array
    {
        return $this->recordsFromDataFile('compositions.php');
    }
}
