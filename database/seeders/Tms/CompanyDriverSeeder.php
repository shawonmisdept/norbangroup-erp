<?php

namespace Database\Seeders\Tms;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsVehicle;
use Illuminate\Database\Seeder;

class CompanyDriverSeeder extends Seeder
{
    public function run(): void
    {
        $rows = require database_path('seeders/data/tms_company_drivers.php');

        $headOfficeFactoryId = Factory::query()
            ->where('name', 'Head Office')
            ->where('is_active', true)
            ->value('id');

        if (! $headOfficeFactoryId) {
            $this->command?->error('Head Office factory not found — run FactorySeeder first.');

            return;
        }

        $vehiclesByReg = TmsVehicle::query()
            ->get(['id', 'reg_number'])
            ->keyBy(fn (TmsVehicle $vehicle) => $this->normalizeRegNumber($vehicle->reg_number));

        $created = 0;
        $updated = 0;
        $phonesUpdated = 0;
        $assignments = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $employee = Employee::query()
                ->where('employee_code', $row['employee_code'])
                ->where('factory_id', $headOfficeFactoryId)
                ->first();

            if (! $employee) {
                $this->command?->warn("Employee {$row['employee_code']} not found — skipped.");
                $skipped++;

                continue;
            }

            if (! empty($row['contact_phone']) && $employee->phone !== $row['contact_phone']) {
                $employee->update(['phone' => $row['contact_phone']]);
                $phonesUpdated++;
            }

            $vehicleIds = [];
            foreach ($row['vehicles'] as $regNumber) {
                $normalized = $this->normalizeRegNumber($regNumber);
                $vehicle = $vehiclesByReg->get($normalized);

                if (! $vehicle) {
                    $this->command?->warn("Vehicle {$regNumber} not found for employee {$row['employee_code']} — skipped assignment.");
                    $skipped++;

                    continue;
                }

                $vehicleIds[] = $vehicle->id;
            }

            if ($vehicleIds === []) {
                $this->command?->warn("No vehicles resolved for employee {$row['employee_code']} — skipped driver.");
                $skipped++;

                continue;
            }

            $primaryVehicleId = $vehicleIds[0];

            $driver = TmsDriver::query()->updateOrCreate(
                [
                    'factory_id'  => $headOfficeFactoryId,
                    'employee_id' => $employee->id,
                ],
                [
                    'default_vehicle_id' => $primaryVehicleId,
                    'status'             => 'active',
                    'is_overtime_active' => true,
                    'ot_rate'            => 0,
                ]
            );

            $driver->wasRecentlyCreated ? $created++ : $updated++;

            $driver->syncAssignedVehicles($vehicleIds, $primaryVehicleId);

            foreach ($vehicleIds as $vehicleId) {
                TmsVehicle::query()
                    ->whereKey($vehicleId)
                    ->update(['primary_driver_id' => $driver->id]);
                $assignments++;
            }
        }

        $this->command?->info(
            "TMS company drivers seeded: {$created} created, {$updated} updated, {$assignments} vehicle links, {$phonesUpdated} phones updated, {$skipped} skipped."
        );
    }

    private function normalizeRegNumber(string $regNumber): string
    {
        $parts = preg_split('/[\s\-]+/', trim($regNumber)) ?: [];

        return implode('-', array_map(
            static fn (string $part) => strtoupper($part),
            array_values(array_filter($parts, static fn (string $part) => $part !== ''))
        ));
    }
}
