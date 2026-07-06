<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class KbSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            KbModuleSeeder::class,
            KbArticleSeeder::class,
        ]);
    }
}
