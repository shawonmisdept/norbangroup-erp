<?php

namespace Database\Seeders\Masters;

use App\Models\Color;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return Color::class;
    }

    protected function records(): array
    {
        return [
            ['name' => 'Navy Blue', 'hex_code' => '#1E3A5F'],
            ['name' => 'Black', 'hex_code' => '#000000'],
            ['name' => 'White', 'hex_code' => '#FFFFFF'],
            ['name' => 'Red', 'hex_code' => '#DC2626'],
            ['name' => 'Heather Grey', 'hex_code' => '#9CA3AF'],
            ['name' => 'Olive Green', 'hex_code' => '#556B2F'],
        ];
    }
}
