<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\Building;
use App\Models\Hrm\Floor;
use App\Models\Hrm\Line;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            'Production Block' => [
                'Ground Floor' => [
                    'floor_number' => 0,
                    'lines'        => ['Line 1', 'Line 2', 'Line 3', 'Line 4', 'Line 5'],
                ],
                '1st Floor' => [
                    'floor_number' => 1,
                    'lines'        => ['Line 6', 'Line 7', 'Line 8', 'Line 9', 'Line 10'],
                ],
            ],
            'Finishing Block' => [
                'Ground Floor' => [
                    'floor_number' => 0,
                    'lines'        => ['Finishing 1', 'Finishing 2', 'Packing'],
                ],
            ],
            'Merchandising Building' => [
                'Ground Floor' => [
                    'floor_number' => 0,
                    'lines'        => [],
                ],
            ],
            'Admin Building' => [
                'Ground Floor' => [
                    'floor_number' => 0,
                    'lines'        => [],
                ],
            ],
        ];

        /** @var array{factory: string} $headOfficeOrg */
        $headOfficeOrg = require database_path('seeders/data/head_office_org.php');

        $skipFactories = [$headOfficeOrg['factory']];

        foreach (Factory::where('is_active', true)->whereNotIn('name', $skipFactories)->get() as $factory) {
            $buildingNames = array_keys($structure);

            foreach ($structure as $buildingName => $floors) {
                $building = Building::updateOrCreate(
                    ['factory_id' => $factory->id, 'name' => $buildingName],
                    ['description' => 'Seeded production structure', 'is_active' => true]
                );

                $floorNames = array_keys($floors);

                foreach ($floors as $floorName => $floorData) {
                    $floor = Floor::updateOrCreate(
                        ['building_id' => $building->id, 'name' => $floorName],
                        [
                            'factory_id'   => $factory->id,
                            'floor_number' => $floorData['floor_number'],
                            'is_active'    => true,
                        ]
                    );

                    Line::where('floor_id', $floor->id)
                        ->whereNotIn('name', $floorData['lines'])
                        ->update(['is_active' => false]);

                    foreach ($floorData['lines'] as $lineName) {
                        Line::updateOrCreate(
                            ['floor_id' => $floor->id, 'name' => $lineName],
                            ['factory_id' => $factory->id, 'is_active' => true]
                        );
                    }
                }

                Floor::where('building_id', $building->id)
                    ->whereNotIn('name', $floorNames)
                    ->update(['is_active' => false]);
            }

            Building::where('factory_id', $factory->id)
                ->whereNotIn('name', $buildingNames)
                ->update(['is_active' => false]);
        }
    }
}
