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

    public function test_vehicle_edit_allocated_user_lists_employees_from_all_units_for_cross_unit_user(): void
    {
        $ho = Factory::create(['name' => 'Head Office', 'is_active' => true]);
        $ncl = Factory::create(['name' => 'Norban Comtex Limited', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Transport Admin',
            'permissions' => ['tms.dashboard.view', 'tms.vehicles.view', 'tms.vehicles.manage'],
        ]);

        $user = User::create([
            'name'       => 'Cross Unit Transport',
            'email'      => 'transport-cross@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => null,
        ]);

        $shiftHo = \App\Models\Hrm\Shift::create([
            'factory_id' => $ho->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);
        $shiftNcl = \App\Models\Hrm\Shift::create([
            'factory_id' => $ncl->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);

        \App\Models\Hrm\Employee::create([
            'factory_id' => $ho->id, 'shift_id' => $shiftHo->id,
            'employee_code' => 'HO-X1', 'name' => 'HO Officer', 'status' => 'active',
        ]);
        \App\Models\Hrm\Employee::create([
            'factory_id' => $ncl->id, 'shift_id' => $shiftNcl->id,
            'employee_code' => 'NCL-X1', 'name' => 'NCL Officer', 'status' => 'active',
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'         => $ho->id,
            'name'               => 'Test Car',
            'reg_number'         => 'DM-TEST-01',
            'type'               => 'own',
            'fuel_type'          => 'octane',
            'passenger_capacity' => 4,
            'status'             => 'available',
            'registration_paper_status' => 'ok',
        ]);

        $this->actingAs($user)
            ->get(route('admin.tms.vehicles.edit', $vehicle))
            ->assertOk()
            ->assertSee('HO Officer', false)
            ->assertSee('NCL Officer', false)
            ->assertSee('Norban Comtex Limited', false);
    }

    public function test_vehicle_can_assign_primary_driver_from_another_unit(): void
    {
        $ho = Factory::create(['name' => 'Head Office', 'is_active' => true]);
        $bdCom = Factory::create(['name' => 'BD Com', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Transport Admin',
            'permissions' => ['tms.dashboard.view', 'tms.vehicles.view', 'tms.vehicles.manage'],
        ]);

        $user = User::create([
            'name'       => 'Cross Unit Transport',
            'email'      => 'transport-cross-driver@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => null,
        ]);

        $employee = \App\Models\Hrm\Employee::create([
            'factory_id'    => $ho->id,
            'employee_code' => 'HO-DRV-1',
            'name'          => 'Ismail Sarkar',
            'status'        => 'active',
        ]);

        $driver = \App\Models\Tms\TmsDriver::create([
            'factory_id'  => $ho->id,
            'employee_id' => $employee->id,
            'status'      => 'active',
            'ot_rate'     => 0,
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'                => $bdCom->id,
            'name'                      => 'BMW Jeep',
            'reg_number'                => 'DM-GHA-22-1042',
            'type'                      => 'own',
            'fuel_type'                 => 'octane',
            'passenger_capacity'        => 5,
            'status'                    => 'available',
            'registration_paper_status' => 'ok',
        ]);

        $this->actingAs($user)
            ->put(route('admin.tms.vehicles.update', $vehicle), [
                'factory_id'                => $bdCom->id,
                'name'                      => 'BMW Jeep',
                'reg_number'                => 'DM-GHA-22-1042',
                'type'                      => 'own',
                'fuel_type'                 => 'octane',
                'passenger_capacity'        => 5,
                'status'                    => 'available',
                'registration_paper_status' => 'ok',
                'primary_driver_id'         => $driver->id,
            ])
            ->assertRedirect(route('admin.tms.vehicles.show', $vehicle));

        $this->assertSame($driver->id, $vehicle->fresh()->primary_driver_id);
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
