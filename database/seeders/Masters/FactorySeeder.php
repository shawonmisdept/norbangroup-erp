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
                'address' => 'House # 8/B, Road # 1, Gulshan – 1, Dhaka – 1212, Bangladesh',
                'phone'   => '+88-09666-707635',
            ],
            [
                'name'    => 'Norban Comtex Limited',
                'address' => null,
                'phone'   => null,
            ],
            [
                'name'    => 'Hornbill Apparel Ltd',
                'address' => null,
                'phone'   => null,
            ],
            [
                'name'    => 'Fiber @ Home',
                'address' => null,
                'phone'   => null,
            ],
            [
                'name'    => 'DHL',
                'address' => null,
                'phone'   => null,
            ],
            [
                'name'    => 'BD Com',
                'address' => null,
                'phone'   => null,
            ],
        ];

        $activeNames = collect($records)->pluck('name');

        Factory::whereNotIn('name', $activeNames)->update(['is_active' => false]);

        foreach ($records as $record) {
            Factory::updateOrCreate(
                ['name' => $record['name']],
                array_merge($record, ['is_active' => true])
            );
        }
    }
}
