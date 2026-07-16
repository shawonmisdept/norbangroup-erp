<?php

namespace Tests\Feature;

use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsVehicle;
use Database\Seeders\Masters\FactorySeeder;
use Database\Seeders\Tms\MaintenanceSeeder;
use Database\Seeders\Tms\VehicleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsMaintenanceSeederGapTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{bills: int, items: int, sample_bill: string}> */
    private function expectedMaintenanceByReg(): array
    {
        return [
            'DM-GA-30-0062' => [
                'bills'       => 2,
                'items'       => 11,
                'sample_bill' => '14801',
            ],
            'DM-KHA-23-5772' => [
                'bills'       => 14,
                'items'       => 74,
                'sample_bill' => '1814',
            ],
            'DM-U-4801' => [
                'bills'       => 7,
                'items'       => 28,
                'sample_bill' => 'Bill # 21494',
            ],
        ];
    }

    public function test_maintenance_seeder_links_all_three_user_provided_vehicles(): void
    {
        $this->seed(FactorySeeder::class);
        $this->seed(VehicleSeeder::class);
        $this->seed(MaintenanceSeeder::class);

        $seedData = require database_path('seeders/data/tms_maintenance.php');

        foreach ($this->expectedMaintenanceByReg() as $reg => $expected) {
            $vehicle = TmsVehicle::where('reg_number', $reg)->first();
            $this->assertNotNull($vehicle, "Vehicle missing: {$reg}");

            $seedBills = $seedData[$reg]['bills'] ?? [];
            $this->assertCount($expected['bills'], $seedBills, "Seed file bill count mismatch for {$reg}");
            $this->assertSame(
                $expected['items'],
                array_sum(array_map(static fn (array $bill) => count($bill['items'] ?? []), $seedBills)),
                "Seed file item count mismatch for {$reg}"
            );

            $bills = TmsMaintenanceBill::where('vehicle_id', $vehicle->id)->get();

            $this->assertCount($expected['bills'], $bills, "DB bill count mismatch for {$reg}");
            $this->assertSame(
                $expected['items'],
                $bills->sum(fn (TmsMaintenanceBill $bill) => $bill->items()->count()),
                "DB item count mismatch for {$reg}"
            );
            $this->assertTrue(
                $bills->contains('bill_no', $expected['sample_bill']),
                "Sample bill {$expected['sample_bill']} missing for {$reg}"
            );
        }
    }

    public function test_seed_integrity_script_reports_zero_orphan_maintenance(): void
    {
        $output = shell_exec('php ' . escapeshellarg(database_path('seeders/scripts/audit_tms_seed_integrity.php')));

        $this->assertIsString($output);
        $this->assertStringContainsString('Orphan maintenance (no vehicle): 0', $output);
    }
}
