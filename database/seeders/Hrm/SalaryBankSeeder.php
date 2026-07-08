<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\SalaryBank;
use Illuminate\Database\Seeder;

class SalaryBankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            ['code' => 'SJIB', 'name' => 'Shahjalal Islami Bank PLC', 'short_name' => 'Shahjalal Islami Bank'],
            ['code' => 'BRAC', 'name' => 'BRAC Bank PLC', 'short_name' => 'BRAC Bank'],
            ['code' => 'DBBL', 'name' => 'Dutch-Bangla Bank PLC', 'short_name' => 'Dutch-Bangla Bank'],
            ['code' => 'IBBL', 'name' => 'Islami Bank Bangladesh PLC', 'short_name' => 'Islami Bank'],
            ['code' => 'EBL', 'name' => 'Eastern Bank PLC', 'short_name' => 'Eastern Bank'],
            ['code' => 'MTBL', 'name' => 'Mutual Trust Bank PLC', 'short_name' => 'Mutual Trust Bank'],
            ['code' => 'UCBL', 'name' => 'UCB PLC', 'short_name' => 'UCB'],
        ];

        Factory::query()->each(function (Factory $factory) use ($banks) {
            foreach ($banks as $bank) {
                SalaryBank::updateOrCreate(
                    ['factory_id' => $factory->id, 'code' => $bank['code']],
                    array_merge($bank, ['is_active' => true])
                );
            }
        });
    }
}
