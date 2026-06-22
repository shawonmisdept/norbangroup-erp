<?php

namespace Database\Seeders\Masters;

use App\Models\CompanyCalendar;
use App\Models\Factory;
use Illuminate\Database\Seeder;

class CompanyCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $norban   = Factory::where('name', 'Norban Comtex Limited')->value('id');
        $hornbill = Factory::where('name', 'Hornbill Apparal Limited')->value('id');

        $records = [
            [
                'name'          => 'International Mother Language Day',
                'calendar_type' => 'National Holiday',
                'start_date'    => '2026-02-21',
                'end_date'      => '2026-02-21',
                'description'   => 'National holiday — all factories closed.',
                'factory_id'    => null,
            ],
            [
                'name'          => 'Independence Day',
                'calendar_type' => 'National Holiday',
                'start_date'    => '2026-03-26',
                'end_date'      => '2026-03-26',
                'description'   => 'National holiday — all factories closed.',
                'factory_id'    => null,
            ],
            [
                'name'          => 'Eid-ul-Fitr',
                'calendar_type' => 'Religious Holiday',
                'start_date'    => '2026-03-21',
                'end_date'      => '2026-03-23',
                'description'   => 'Eid holidays — all factories closed.',
                'factory_id'    => null,
            ],
            [
                'name'          => 'Labour Day',
                'calendar_type' => 'National Holiday',
                'start_date'    => '2026-05-01',
                'end_date'      => '2026-05-01',
                'description'   => 'May Day — all factories closed.',
                'factory_id'    => null,
            ],
            [
                'name'          => 'Eid-ul-Adha',
                'calendar_type' => 'Religious Holiday',
                'start_date'    => '2026-05-28',
                'end_date'      => '2026-05-30',
                'description'   => 'Eid-ul-Adha holidays — all factories closed.',
                'factory_id'    => null,
            ],
            [
                'name'          => 'Norban Comtex — Annual Maintenance',
                'calendar_type' => 'Factory Off',
                'start_date'    => '2026-06-15',
                'end_date'      => '2026-06-16',
                'description'   => 'Scheduled machine maintenance and electrical inspection.',
                'factory_id'    => $norban,
            ],
            [
                'name'          => 'Hornbill Apparel — Annual Maintenance',
                'calendar_type' => 'Factory Off',
                'start_date'    => '2026-07-10',
                'end_date'      => '2026-07-11',
                'description'   => 'Scheduled machine maintenance and line overhaul.',
                'factory_id'    => $hornbill,
            ],
            [
                'name'          => 'Victory Day',
                'calendar_type' => 'National Holiday',
                'start_date'    => '2026-12-16',
                'end_date'      => '2026-12-16',
                'description'   => 'National holiday — all factories closed.',
                'factory_id'    => null,
            ],
            [
                'name'          => 'Weekly Friday Off',
                'calendar_type' => 'Weekend',
                'start_date'    => '2026-01-01',
                'end_date'      => '2026-12-31',
                'description'   => 'Standard weekly off day for all factories.',
                'factory_id'    => null,
            ],
        ];

        foreach ($records as $record) {
            CompanyCalendar::updateOrCreate(
                ['name' => $record['name'], 'start_date' => $record['start_date']],
                array_merge($record, ['is_active' => true])
            );
        }
    }
}
