<?php

namespace Database\Seeders\Masters;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            [
                'name'           => 'BRAC Bank Limited',
                'branch'         => 'Gulshan Avenue, Dhaka',
                'account_name'   => 'Head Office — Norbangroup',
                'account_number' => '1501201234567890',
                'routing_number' => '060270522',
                'swift_code'     => 'BRAKBDDH',
                'country'        => 'Bangladesh',
            ],
            [
                'name'           => 'Dutch-Bangla Bank PLC',
                'branch'         => 'Motijheel, Dhaka',
                'account_name'   => 'Head Office — Norbangroup',
                'account_number' => '104.110.0001234',
                'routing_number' => '090270435',
                'swift_code'     => 'DBBLBDDH',
                'country'        => 'Bangladesh',
            ],
            [
                'name'           => 'Islami Bank Bangladesh PLC',
                'branch'         => 'Narayanganj Branch',
                'account_name'   => 'Head Office — Norbangroup',
                'account_number' => '2050123456789',
                'routing_number' => '125670789',
                'swift_code'     => 'IBBLBDDH',
                'country'        => 'Bangladesh',
            ],
            [
                'name'           => 'Eastern Bank PLC',
                'branch'         => 'Gulshan, Dhaka',
                'account_name'   => 'Head Office — Norbangroup',
                'account_number' => '1011209876543',
                'routing_number' => '095260123',
                'swift_code'     => 'EBLDBDDH',
                'country'        => 'Bangladesh',
            ],
            [
                'name'           => 'Standard Chartered Bank',
                'branch'         => 'Gulshan, Dhaka',
                'account_name'   => 'Head Office — Norbangroup',
                'account_number' => '01-2345678-01',
                'routing_number' => '215261234',
                'swift_code'     => 'SCBLBDDX',
                'country'        => 'Bangladesh',
            ],
        ];

        foreach ($records as $record) {
            Bank::updateOrCreate(
                ['name' => $record['name'], 'account_number' => $record['account_number']],
                array_merge($record, ['is_active' => true])
            );
        }
    }
}
