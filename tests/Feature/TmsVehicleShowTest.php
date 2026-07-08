<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Role;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsVehicleShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_style_vehicle_show_page_loads(): void
    {
        $factory = Factory::create(['name' => 'Head Office', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Transport Officer',
            'permissions' => ['tms.dashboard.view', 'tms.vehicles.view', 'tms.vehicles.manage'],
        ]);

        $user = User::create([
            'name'       => 'Transport',
            'email'      => 'transport-show@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'                => $factory->id,
            'name'                      => 'BMW Jeep',
            'model_year'                => 2022,
            'engine_cc'                 => 2998,
            'reg_number'                => 'DM-GHA-22-1042',
            'type'                      => 'own',
            'fuel_type'                 => 'octane',
            'passenger_capacity'        => 5,
            'status'                    => 'available',
            'purchase_date'             => '2023-01-10',
            'registration_date'         => '2023-01-10',
            'purchase_value'            => 28847000,
            'fitness_expires_at'        => '2027-01-01',
            'tax_token_expires_at'      => '2027-01-09',
            'insurance_expires_at'      => '2027-01-20',
            'registration_paper_status' => 'ok',
        ]);

        $this->actingAs($user)
            ->get(route('admin.tms.vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('BMW Jeep')
            ->assertSee('DM-GHA-22-1042');
    }

    public function test_vehicle_seeder_data_maps_paper_fields(): void
    {
        Factory::create(['name' => 'Head Office', 'is_active' => true]);

        $this->seed(\Database\Seeders\Tms\VehicleSeeder::class);

        $vehicle = TmsVehicle::where('reg_number', 'DM-GHA-22-1042')->first();

        $this->assertNotNull($vehicle);
        $this->assertSame('BMW Jeep', $vehicle->name);
        $this->assertSame(2022, $vehicle->model_year);
        $this->assertSame(2998, $vehicle->engine_cc);
        $this->assertSame('octane', $vehicle->fuel_type);
        $this->assertSame('2027-01-01', $vehicle->fitness_expires_at->toDateString());
        $this->assertSame('2027-01-09', $vehicle->tax_token_expires_at->toDateString());
        $this->assertSame('2027-01-20', $vehicle->insurance_expires_at->toDateString());
        $this->assertNull($vehicle->allocated_employee_id);
        $this->assertNull($vehicle->primary_driver_id);
    }

    public function test_vehicle_seeder_respects_blank_sheet_cells(): void
    {
        Factory::create(['name' => 'Head Office', 'is_active' => true]);

        $this->seed(\Database\Seeders\Tms\VehicleSeeder::class);

        $vehicle = TmsVehicle::where('reg_number', 'DM-TA-13-6693')->first();

        $this->assertNotNull($vehicle);
        $this->assertSame('Covered Van', $vehicle->name);
        $this->assertSame('2023-07-20', $vehicle->purchase_date->toDateString());
        $this->assertSame('2019-01-15', $vehicle->registration_date->toDateString());
        $this->assertSame(1400000, (int) $vehicle->purchase_value);
        $this->assertSame('2027-06-14', $vehicle->fitness_expires_at->toDateString());
        $this->assertSame('2027-01-15', $vehicle->tax_token_expires_at->toDateString());
        $this->assertNull($vehicle->insurance_expires_at);
        $this->assertSame('2028-01-15', $vehicle->route_permit_expires_at->toDateString());
    }
}
