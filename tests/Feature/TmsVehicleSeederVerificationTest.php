<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Tms\TmsVehicle;
use Database\Seeders\Masters\FactorySeeder;
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
            'DM-GA-30-0062',
            'DM-KHA-23-5772',
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
            'DM-U-4801',
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
            'DM-KHA-23-5772' => 'Toyota Axio',
            'DM-GA-30-0062'  => 'Toyota Axio',
            'NAR-MA-11-0043' => 'Cover Van',
            'DM-U-4801'      => 'Covered Van',
            'DM-GHA-16-2903' => 'Hard Jeep (BYD)',
            'DM-GHA-21-5864' => 'Hard Jeep,Toyota',
            'DM-THA-11-7867' => 'Pic up Double cabin',
            'DM-GA-13-9120'  => 'Toyota Corrola',
            'DM-CHA-52-4571' => 'Microbus (Alphard)',
            'DM-CHA-53-4286' => 'HIACE (Microbus)',
            'DM-CHA-11-3870' => 'NOAH (Microbus)',
            'DM-CHA-56-0973' => 'URBAN (Microbus)',
        ];
    }

    /** @return array<string, array{0: string, 1: int}> */
    private function expectedCategoryAndSeatsByReg(): array
    {
        return [
            'DM-GHA-22-1042' => ['jeep', 5],
            'DM-GA-42-0117'  => ['sedan', 5],
            'DM-GHA-13-8951' => ['jeep', 7],
            'DM-CHA-52-4571' => ['microbus', 7],
            'DM-THA-11-7867' => ['pickup', 5],
            'DM-CHA-53-4286' => ['microbus', 12],
            'DM-CHA-11-3870' => ['microbus', 8],
            'DM-CHA-56-0973' => ['microbus', 10],
            'DM-MA-11-6078'  => ['other', 3],
            'DM-GA-35-4897'  => ['sedan', 5],
        ];
    }

    public function test_seed_data_file_contains_all_fifty_one_spreadsheet_vehicles(): void
    {
        $rows = require database_path('seeders/data/tms_vehicles.php');

        $this->assertCount(51, $rows);

        $regs = array_map(
            static fn (array $row) => strtoupper($row['reg_number']),
            $rows,
        );

        $this->assertSame($this->expectedRegNumbers(), $regs);
    }

    public function test_vehicle_seeder_inserts_all_fifty_one_vehicles(): void
    {
        $this->seed(FactorySeeder::class);
        $this->seed(VehicleSeeder::class);

        $this->assertSame(51, TmsVehicle::count());

        foreach ($this->expectedRegNumbers() as $reg) {
            $this->assertDatabaseHas('tms_vehicles', [
                'reg_number' => $reg,
            ]);
        }
    }

    public function test_vehicle_seeder_vehicle_names_match_spreadsheet(): void
    {
        $this->seed(FactorySeeder::class);
        $this->seed(VehicleSeeder::class);

        foreach ($this->expectedNamesByReg() as $reg => $name) {
            $vehicle = TmsVehicle::where('reg_number', $reg)->first();

            $this->assertNotNull($vehicle, "Missing vehicle: {$reg}");
            $this->assertSame($name, $vehicle->name, "Wrong name for {$reg}");
        }
    }

    public function test_vehicle_seeder_sets_category_and_seats_by_model(): void
    {
        $this->seed(FactorySeeder::class);
        $this->seed(VehicleSeeder::class);

        foreach ($this->expectedCategoryAndSeatsByReg() as $reg => [$category, $seats]) {
            $vehicle = TmsVehicle::where('reg_number', $reg)->first();

            $this->assertNotNull($vehicle, "Missing vehicle: {$reg}");
            $this->assertSame($category, $vehicle->vehicle_category, "Wrong category for {$reg}");
            $this->assertSame($seats, $vehicle->passenger_capacity, "Wrong seats for {$reg}");
        }

        $rows = require database_path('seeders/data/tms_vehicles.php');
        foreach ($rows as $row) {
            $this->assertNotEmpty($row['vehicle_category'] ?? null, 'Missing category for '.$row['reg_number']);
            $this->assertGreaterThan(0, (int) ($row['passenger_capacity'] ?? 0), 'Missing seats for '.$row['reg_number']);
        }
    }

    public function test_vehicle_seeder_blank_sheet_cells_stay_null(): void
    {
        $this->seed(FactorySeeder::class);
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

    public function test_vehicle_seeder_paper_dates_match_sheet1(): void
    {
        $this->seed(FactorySeeder::class);
        $this->seed(VehicleSeeder::class);

        // Sheet1 dates (not Sheet2) for regs that differ between sheets
        $cases = [
            'DM-GA-42-0117' => [
                'tax_token_expires_at' => '2026-10-09',
                'insurance_expires_at' => '2026-10-10',
            ],
            'DM-BHA-11-0813' => [
                'fitness_expires_at' => '2028-03-20',
                'tax_token_expires_at' => '2027-04-18',
                'insurance_expires_at' => '2027-05-31',
            ],
            'DM-GHA-21-7771' => [
                'tax_token_expires_at' => '2027-06-22',
            ],
            'DM-THA-11-7867' => [
                'fitness_expires_at' => '2027-06-01',
                'route_permit_expires_at' => '2026-08-08',
            ],
            'DM-GA-15-7196' => [
                'fitness_expires_at' => '2028-03-22',
                'tax_token_expires_at' => '2027-05-12',
            ],
            'DM-CHA-56-0973' => [
                'fitness_expires_at' => '2028-05-31',
                'route_permit_expires_at' => '2026-09-09',
            ],
        ];

        foreach ($cases as $reg => $fields) {
            $vehicle = TmsVehicle::where('reg_number', $reg)->first();
            $this->assertNotNull($vehicle, "Missing vehicle: {$reg}");

            foreach ($fields as $column => $expected) {
                $actual = $vehicle->{$column}?->format('Y-m-d');
                $this->assertSame($expected, $actual, "Wrong {$column} for {$reg}");
            }
        }
    }

    public function test_vehicle_seeder_assigns_factory_from_sheet1_owner(): void
    {
        $this->seed(FactorySeeder::class);
        $this->seed(VehicleSeeder::class);

        $cases = [
            'DM-GHA-22-1042' => 'NCL',
            'DM-GA-42-0117'  => 'BD Com',
            'DM-GHA-02-0005' => 'HAL',
            'DM-BHA-11-0813' => 'NFL',
            'DM-GHA-21-5864' => 'DHL',
        ];

        foreach ($cases as $reg => $factoryName) {
            $vehicle = TmsVehicle::with('factory')->where('reg_number', $reg)->first();
            $this->assertNotNull($vehicle, "Missing vehicle: {$reg}");
            $this->assertSame($factoryName, $vehicle->factory?->name, "Wrong unit for {$reg}");
        }

        $rows = require database_path('seeders/data/tms_vehicles.php');
        foreach ($rows as $row) {
            $this->assertNotEmpty($row['unit'] ?? null, 'Missing unit for '.$row['reg_number']);
        }
    }

    public function test_vehicle_seeder_removes_orphan_records_not_in_spreadsheet(): void
    {
        $this->seed(FactorySeeder::class);
        $factory = Factory::where('name', 'Head Office')->firstOrFail();

        $this->seed(VehicleSeeder::class);
        $this->assertSame(51, TmsVehicle::count());

        foreach ([
            ['reg_number' => 'DM-GA-23-5772', 'name' => 'Toyota'],
            ['reg_number' => 'DM-U-11-4801', 'name' => 'Toyota'],
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

        $this->assertSame(51, TmsVehicle::count());
        $this->assertNull(TmsVehicle::where('reg_number', 'DM-GA-23-5772')->first());
        $this->assertNull(TmsVehicle::where('reg_number', 'DM-U-11-4801')->first());
        $this->assertNotNull(TmsVehicle::where('reg_number', 'DM-KHA-23-5772')->first());
        $this->assertNotNull(TmsVehicle::where('reg_number', 'DM-GA-30-0062')->first());
        $this->assertNotNull(TmsVehicle::where('reg_number', 'DM-U-4801')->first());
    }
}
