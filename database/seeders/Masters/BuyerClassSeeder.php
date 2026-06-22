<?php

namespace Database\Seeders\Masters;

use App\Models\Buyer;
use App\Models\BuyerClass;
use Illuminate\Database\Seeder;

class BuyerClassSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['name' => "Men's Wear", 'buyer' => 'H&M'],
            ['name' => "Women's Wear", 'buyer' => 'H&M'],
            ['name' => "Kids Wear", 'buyer' => 'Zara'],
            ['name' => 'Denim', 'buyer' => 'C&A'],
            ['name' => 'Knitwear', 'buyer' => 'Primark'],
        ];

        foreach ($records as $record) {
            BuyerClass::updateOrCreate(
                ['name' => $record['name'], 'buyer_id' => Buyer::where('name', $record['buyer'])->value('id')],
                ['is_active' => true]
            );
        }
    }
}
