<?php

namespace Database\Seeders\Tms;

use App\Models\Factory;
use App\Models\Tms\TmsVehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = require database_path('seeders/data/tms_vehicles.php');

        $factoryIds = Factory::query()
            ->where('is_active', true)
            ->pluck('id', 'name');

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $factoryId = $factoryIds[$row['unit']] ?? null;

            if (! $factoryId) {
                $this->command?->warn("Unit \"{$row['unit']}\" not found — skipped {$row['reg_number']}.");
                $skipped++;

                continue;
            }

            $vehicle = TmsVehicle::withTrashed()->updateOrCreate(
                [
                    'factory_id' => $factoryId,
                    'reg_number' => $row['reg_number'],
                ],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'fuel_type' => $row['fuel_type'],
                    'passenger_capacity' => $row['passenger_capacity'],
                    'status' => $row['status'],
                    'deleted_at' => null,
                ]
            );

            $vehicle->wasRecentlyCreated ? $created++ : $updated++;
        }

        $this->command?->info("TMS vehicles seeded: {$created} created, {$updated} updated, {$skipped} skipped.");
    }
}
