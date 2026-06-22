<?php

namespace Database\Seeders\Masters;

use App\Models\Brand;
use App\Models\Buyer;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['name' => 'H&M Essentials', 'buyer' => 'H&M'],
            ['name' => 'Zara Man', 'buyer' => 'Zara'],
            ['name' => 'Zara Woman', 'buyer' => 'Zara'],
            ['name' => 'C&A Basics', 'buyer' => 'C&A'],
            ['name' => 'Primark Core', 'buyer' => 'Primark'],
        ];

        foreach ($records as $record) {
            Brand::updateOrCreate(
                ['name' => $record['name']],
                [
                    'buyer_id'  => Buyer::where('name', $record['buyer'])->value('id'),
                    'is_active' => true,
                ]
            );
        }
    }
}
