<?php

namespace Database\Seeders\Masters;

use App\Models\TrimsStatus;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class TrimsStatusSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return TrimsStatus::class; }

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
