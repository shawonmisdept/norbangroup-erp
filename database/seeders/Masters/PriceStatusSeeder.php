<?php

namespace Database\Seeders\Masters;

use App\Models\PriceStatus;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class PriceStatusSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string { return PriceStatus::class; }

    protected function records(): array
    {
        return [
            ['name' => 'Pending Quote'],
            ['name' => 'Quoted'],
            ['name' => 'Negotiating'],
            ['name' => 'Confirmed'],
            ['name' => 'Revised'],
        ];
    }
}
