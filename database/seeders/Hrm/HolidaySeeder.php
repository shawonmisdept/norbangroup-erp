<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['name' => 'International Mother Language Day', 'date' => '2026-02-21', 'is_optional' => false],
            ['name' => 'Independence Day', 'date' => '2026-03-26', 'is_optional' => false],
            ['name' => 'Eid-ul-Fitr', 'date' => '2026-03-21', 'is_optional' => false],
            ['name' => 'Eid-ul-Fitr', 'date' => '2026-03-22', 'is_optional' => false],
            ['name' => 'Eid-ul-Fitr', 'date' => '2026-03-23', 'is_optional' => false],
            ['name' => 'Labour Day', 'date' => '2026-05-01', 'is_optional' => false],
            ['name' => 'Eid-ul-Adha', 'date' => '2026-05-28', 'is_optional' => false],
            ['name' => 'Eid-ul-Adha', 'date' => '2026-05-29', 'is_optional' => false],
            ['name' => 'Eid-ul-Adha', 'date' => '2026-05-30', 'is_optional' => false],
            ['name' => 'Victory Day', 'date' => '2026-12-16', 'is_optional' => false],
        ];

        foreach (Factory::where('is_active', true)->get() as $factory) {
            foreach ($holidays as $holiday) {
                $attributes = [
                    'is_optional' => $holiday['is_optional'],
                    'description' => 'National / festival holiday',
                    'is_active'   => true,
                ];

                $record = Holiday::query()
                    ->where('factory_id', $factory->id)
                    ->where('name', $holiday['name'])
                    ->whereDate('date', $holiday['date'])
                    ->first();

                if ($record) {
                    $record->update($attributes);
                } else {
                    Holiday::create(array_merge($attributes, [
                        'factory_id' => $factory->id,
                        'name'       => $holiday['name'],
                        'date'       => $holiday['date'],
                    ]));
                }
            }
        }
    }
}
