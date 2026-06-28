<?php

namespace Database\Seeders\Masters;

use App\Models\Factory;
use Illuminate\Database\Seeder;

class FactorySeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            [
                'name'    => 'Head Office',
                'address' => 'Dhaka, Bangladesh',
                'phone'   => '01711111100',
            ],
            [
                'name'    => 'Norban Comtex Limited',
                'address' => 'Narayanganj, Bangladesh',
                'phone'   => '01711111101',
            ],
            [
                'name'    => 'Hornbill Apparal Limited',
                'address' => 'Gazipur, Bangladesh',
                'phone'   => '01711111102',
            ],
        ];

        $names = collect($records)->pluck('name');

        Factory::whereNotIn('name', $names)->update(['is_active' => false]);

        foreach ($records as $record) {
            Factory::updateOrCreate(
                ['name' => $record['name']],
                array_merge($record, ['is_active' => true])
            );
        }
    }
}
