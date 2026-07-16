<?php

namespace Database\Seeders\Masters;

use App\Models\Factory;
use Illuminate\Database\Seeder;

class FactorySeeder extends Seeder
{
    /** @var array<string, string> new name => legacy name */
    private const LEGACY_NAMES = [
        'NCL' => 'Norban Comtex Limited',
        'HAL' => 'Hornbill Apparel Ltd',
    ];

    public function run(): void
    {
        $this->renameLegacyFactories();

        $records = [
            [
                'name'    => 'Head Office',
                'address' => 'House # 8/B, Road # 1, Gulshan – 1, Dhaka – 1212, Bangladesh',
                'phone'   => '+88-09666-707635',
            ],
            [
                'name'    => 'NCL',
                'address' => null,
                'phone'   => null,
            ],
            [
                'name'    => 'HAL',
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
            [
                'name'    => 'NFL',
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

    private function renameLegacyFactories(): void
    {
        foreach (self::LEGACY_NAMES as $newName => $legacyName) {
            $legacy = Factory::query()->where('name', $legacyName)->first();

            if (! $legacy) {
                continue;
            }

            $duplicate = Factory::query()
                ->where('name', $newName)
                ->whereKeyNot($legacy->id)
                ->exists();

            if ($duplicate) {
                $this->command?->warn("Skipping rename {$legacyName} → {$newName}: target name already exists.");

                continue;
            }

            $legacy->update(['name' => $newName]);
        }
    }
}
