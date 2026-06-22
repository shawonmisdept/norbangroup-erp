<?php

namespace Database\Seeders\Masters;

use App\Models\Size;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            ['name' => 'XS', 'sort_order' => 1],
            ['name' => 'S', 'sort_order' => 2],
            ['name' => 'M', 'sort_order' => 3],
            ['name' => 'L', 'sort_order' => 4],
            ['name' => 'XL', 'sort_order' => 5],
            ['name' => 'XXL', 'sort_order' => 6],
            ['name' => '3XL', 'sort_order' => 7],
        ];

        foreach ($sizes as $size) {
            Size::updateOrCreate(['name' => $size['name']], array_merge($size, ['is_active' => true]));
        }
    }
}
