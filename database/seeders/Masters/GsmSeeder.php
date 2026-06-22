<?php

namespace Database\Seeders\Masters;

use App\Models\Gsm;
use Illuminate\Database\Seeder;

class GsmSeeder extends Seeder
{
    public function run(): void
    {
        $values = [120, 140, 160, 180, 200, 220, 240, 280, 320];

        foreach ($values as $value) {
            Gsm::updateOrCreate(
                ['value' => $value],
                ['name' => "{$value} GSM", 'is_active' => true]
            );
        }
    }
}
