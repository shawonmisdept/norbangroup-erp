<?php

namespace Database\Seeders\Masters;

use App\Models\YarnStatus;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class YarnStatusSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return YarnStatus::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Not Started'],
            ['name' => 'Ordered'],
            ['name' => 'In House'],
            ['name' => 'Allocated'],
            ['name' => 'Short'],
        ];
    }
}
