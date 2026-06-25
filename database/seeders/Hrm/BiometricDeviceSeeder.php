<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\BiometricDevice;
use Illuminate\Database\Seeder;

class BiometricDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            [
                'name'        => 'Main Gate Device',
                'location'    => 'Main Gate',
                'description' => 'Primary IN/OUT device at factory entrance — configure IP and ADMS URL',
            ],
            [
                'name'        => 'Production Floor Device',
                'location'    => 'Production Block',
                'description' => 'Secondary device for production floor access',
            ],
        ];

        foreach (Factory::where('is_active', true)->get() as $factory) {
            $deviceNames = collect($devices)->pluck('name');

            foreach ($devices as $device) {
                BiometricDevice::updateOrCreate(
                    ['factory_id' => $factory->id, 'name' => $device['name']],
                    array_merge($device, ['is_active' => true])
                );
            }

            BiometricDevice::where('factory_id', $factory->id)
                ->whereNotIn('name', $deviceNames)
                ->update(['is_active' => false]);
        }
    }
}
