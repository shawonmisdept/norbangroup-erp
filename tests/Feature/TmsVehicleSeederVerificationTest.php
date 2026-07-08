<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Tms\TmsVehicle;
use Database\Seeders\Tms\VehicleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsVehicleSeederVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, string> */
    private function expectedRegNumbers(): array
    {
        return [
            'DM-GHA-22-1042',
            'DM-GA-42-0117',
            'DM-GHA-13-8951',
            'DM-CHA-52-4571',
            'DM-BHA-11-0813',
            'DM-GHA-21-9271',
            'DM-GA-33-1788',
            'DM-GHA-02-0005',
            'DM-GHA-21-9272',
            'DM-GHA-16-2903',
            'DM-GHA-13-1531',
            'DM-GHA-21-7771',
            'DM-THA-11-7867',
            'DM-GHA-21-7770',
            'DM-GA-35-4897',
            'DM-GHA-11-8402',
            'DM-GA-45-2366',
            'DM-GA-35-7990',
            'DM-GA-15-7196',
            'DM-KHA-12-6032',
            'DM-GA-31-4810',
            'DM-GA-23-5772',
            'DM-GA-43-9461',
            'DM-GA-13-5028',
            'DM-KHA-12-1223',
            'DM-KHA-13-1898',
            'DM-GA-13-9120',
            'DM-GA-19-9823',
            'DM-GA-23-3941',
            'DM-GA-37-9232',
            'DM-GA-37-9227',
            'DM-CHA-53-4286',
            'DM-CHA-11-3870',
            'DM-CHA-53-4349',
            'DM-CHA-53-4348',
            'DM-CHA-56-0973',
            'DM-CHA-56-1146',
            'DM-MA-11-6078',
            'DM-MA-11-6079',
            'DM-AU-11-2904',
            'NAR-MA-11-0043',
            'DM-AU-11-4206',
            'DM-AU-14-1095',
            'DM-U-11-4801',
            'DM-MA-51-8450',
            'DM-MA-14-0155',
            'DM-TA-15-7042',
            'DM-TA-13-6693',
            'DM-DA-11-9103',
            'DM-GHA-21-5864',
        ];
    }

    /** @return array<string, string> */
    private function expectedNamesByReg(): array
    {
        return [
            'DM-GHA-22-1042' => 'BMW Jeep',
            'DM-GA-42-0117'  => 'Mercedes Benz',
            'DM-GA-23-5772'  => 'Toyota Axio',
            'NAR-MA-11-0043' => 'Cover Van',
            'DM-U-11-4801'   => 'Covered Van',
            'DM-GHA-21-5864' => 'Hard Jeep,Toyota',
            'DM-THA-11-7867' => 'Pic up Double cabin',
            'DM-GA-13-9120'  => 'Toyota Corrola',
        ];
    }

    public function test_seed_data_file_contains_all_fifty_spreadsheet_vehicles(): void
    {
        $rows = require database_path('seeders/data/tms_vehicles.php');

        $this->assertCount(50, $rows);

        $regs = array_map(
            static fn (array $row) => strtoupper($row['reg_number']),
            $rows,
        );

        $this->assertSame($this->expectedRegNumbers(), $regs);
    }

    public function test_vehicle_seeder_inserts_all_fifty_vehicles(): void
    {
        Factory::create(['name' => 'Head Office', 'is_active' => true]);

        $this->seed(VehicleSeeder::class);

        $this->assertSame(50, TmsVehicle::count());

        foreach ($this->expectedRegNumbers() as $reg) {
            $this->assertDatabaseHas('tms_vehicles', [
                'reg_number' => $reg,
            ]);
        }
    }

    public function test_vehicle_seeder_vehicle_names_match_spreadsheet(): void
    {
        Factory::create(['name' => 'Head Office', 'is_active' => true]);

        $this->seed(VehicleSeeder::class);

        foreach ($this->expectedNamesByReg() as $reg => $name) {
            $vehicle = TmsVehicle::where('reg_number', $reg)->first();

            $this->assertNotNull($vehicle, "Missing vehicle: {$reg}");
            $this->assertSame($name, $vehicle->name, "Wrong name for {$reg}");
        }
    }

    public function test_vehicle_seeder_blank_sheet_cells_stay_null(): void
    {
        Factory::create(['name' => 'Head Office', 'is_active' => true]);

        $this->seed(VehicleSeeder::class);

        $honda = TmsVehicle::where('reg_number', 'DM-GA-15-7196')->first();
        $this->assertNotNull($honda);
        $this->assertNull($honda->purchase_date);

        $corrola = TmsVehicle::where('reg_number', 'DM-GA-13-9120')->first();
        $this->assertNotNull($corrola);
        $this->assertNull($corrola->purchase_date);

        $ta7042 = TmsVehicle::where('reg_number', 'DM-TA-15-7042')->first();
        $this->assertNotNull($ta7042);
        $this->assertNull($ta7042->insurance_expires_at);
    }

    public function test_vehicle_seeder_removes_orphan_records_not_in_spreadsheet(): void
    {
        $factory = Factory::create(['name' => 'Head Office', 'is_active' => true]);

        $this->seed(VehicleSeeder::class);
        $this->assertSame(50, TmsVehicle::count());

        foreach ([
            ['reg_number' => 'DM-GA-30-0062', 'name' => 'Toyota'],
            ['reg_number' => 'DM-KHA-23-5772', 'name' => 'Toyota'],
            ['reg_number' => 'DM-U-4801', 'name' => 'Toyota'],
        ] as $orphan) {
            TmsVehicle::create([
                'factory_id'         => $factory->id,
                'name'               => $orphan['name'],
                'reg_number'         => $orphan['reg_number'],
                'type'               => 'own',
                'fuel_type'          => 'octane',
                'passenger_capacity' => 5,
                'status'             => 'available',
            ]);
        }

        $this->assertSame(53, TmsVehicle::count());

        $this->seed(VehicleSeeder::class);

        $this->assertSame(50, TmsVehicle::count());
        $this->assertNull(TmsVehicle::where('reg_number', 'DM-GA-30-0062')->first());
        $this->assertNull(TmsVehicle::where('reg_number', 'DM-KHA-23-5772')->first());
        $this->assertNull(TmsVehicle::where('reg_number', 'DM-U-4801')->first());
        $this->assertNotNull(TmsVehicle::where('reg_number', 'DM-GA-23-5772')->first());
        $this->assertNotNull(TmsVehicle::where('reg_number', 'DM-U-11-4801')->first());
    }
}
