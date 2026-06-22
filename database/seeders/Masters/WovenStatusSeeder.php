<?php

namespace Database\Seeders\Masters;

use App\Models\WovenStatus;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class WovenStatusSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return WovenStatus::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Not Started'],
            ['name' => 'Grey Fabric Ordered'],
            ['name' => 'In Processing'],
            ['name' => 'Finished Fabric Ready'],
            ['name' => 'Short'],
        ];
    }
}
