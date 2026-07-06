<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            [
                'name'              => 'Day Shift',
                'start_time'        => '09:45:00',
                'end_time'          => '19:00:00',
                'break_minutes'     => 60,
                'break_start_time'  => '13:00:00',
                'break_end_time'    => '14:00:00',
                'is_night'          => false,
                'description'       => 'Office 9:45 AM – 7:00 PM with lunch 1:00–2:00 PM',
            ],
            [
                'name'              => 'Night Shift',
                'start_time'        => '20:00:00',
                'end_time'          => '05:00:00',
                'break_minutes'     => 60,
                'break_start_time'  => '01:00:00',
                'break_end_time'    => '02:00:00',
                'is_night'          => true,
                'description'       => 'Night shift with night allowance eligibility',
            ],
        ];

        foreach (Factory::where('is_active', true)->get() as $factory) {
            $shiftNames = collect($shifts)->pluck('name');

            foreach ($shifts as $shift) {
                Shift::updateOrCreate(
                    ['factory_id' => $factory->id, 'name' => $shift['name']],
                    array_merge($shift, ['is_active' => true])
                );
            }

            Shift::where('factory_id', $factory->id)
                ->whereNotIn('name', $shiftNames)
                ->update(['is_active' => false]);
        }
    }
}
