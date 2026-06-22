<?php

namespace Database\Seeders\Masters;

use App\Models\Buyer;
use Database\Seeders\Concerns\SeedsMasterRecords;
use Illuminate\Database\Seeder;

class BuyerSeeder extends Seeder
{
    use SeedsMasterRecords;

    protected function modelClass(): string
    {
        return Buyer::class;
    }

    protected function records(): array
    {
        return [
            ['name' => 'H&M', 'company' => 'H&M Hennes & Mauritz AB', 'email' => 'buyer@hm.com', 'phone' => '01800000001', 'country' => 'Sweden'],
            ['name' => 'Zara', 'company' => 'Inditex', 'email' => 'buyer@zara.com', 'phone' => '01800000002', 'country' => 'Spain'],
            ['name' => 'C&A', 'company' => 'C&A Mode GmbH & Co. KG', 'email' => 'buyer@ca.com', 'phone' => '01800000003', 'country' => 'Germany'],
            ['name' => 'Primark', 'company' => 'Primark Stores Ltd', 'email' => 'buyer@primark.com', 'phone' => '01800000004', 'country' => 'Ireland'],
        ];
    }
}
