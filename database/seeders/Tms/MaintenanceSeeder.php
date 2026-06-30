<?php

namespace Database\Seeders\Tms;

use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsMaintenanceItem;
use App\Models\Tms\TmsVehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('seeders/data/tms_maintenance.php');

        $vehicles = TmsVehicle::query()
            ->get(['id', 'factory_id', 'reg_number'])
            ->keyBy('reg_number');

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $itemCount = 0;

        foreach ($data as $regNumber => $vehicleData) {
            $vehicle = $vehicles->get($regNumber);

            if (! $vehicle) {
                $this->command?->warn("Vehicle {$regNumber} not found — skipped.");
                $skipped += count($vehicleData['bills']);

                continue;
            }

            foreach ($vehicleData['bills'] as $billRow) {
                if ($billRow['items'] === [] || empty($billRow['bill_date'])) {
                    $skipped++;

                    continue;
                }

                DB::transaction(function () use ($vehicle, $billRow, &$created, &$updated, &$itemCount) {
                    $bill = TmsMaintenanceBill::query()->updateOrCreate(
                        ['bill_no' => $billRow['bill_no']],
                        [
                            'factory_id' => $vehicle->factory_id,
                            'vehicle_id' => $vehicle->id,
                            'bill_date' => $billRow['bill_date'],
                            'workshop_name' => $billRow['workshop_name'],
                            'total_amount' => $billRow['total_amount'],
                            'paid_by' => $billRow['paid_by'] ?? 'company',
                        ]
                    );

                    $bill->wasRecentlyCreated ? $created++ : $updated++;

                    $bill->items()->delete();

                    foreach ($billRow['items'] as $index => $itemRow) {
                        TmsMaintenanceItem::query()->create([
                            'maintenance_bill_id' => $bill->id,
                            'item_name' => $itemRow['item_name'],
                            'quantity' => $itemRow['quantity'],
                            'unit' => $itemRow['unit'],
                            'amount' => $itemRow['amount'],
                            'sort_order' => $index + 1,
                        ]);
                        $itemCount++;
                    }
                });
            }
        }

        $this->command?->info("TMS maintenance seeded: {$created} bills created, {$updated} updated, {$skipped} skipped, {$itemCount} line items.");
    }
}
