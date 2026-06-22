<?php

namespace Database\Seeders\Masters;

use App\Models\AccessoriesStatus;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class AccessoriesStatusSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return AccessoriesStatus::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Not Started'],
            ['name' => 'Ordered'],
            ['name' => 'Partial Received'],
            ['name' => 'In House'],
            ['name' => 'Short'],
        ];
    }
}
