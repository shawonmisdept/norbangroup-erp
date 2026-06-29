<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Role;
use App\Models\Tms\TmsFuelLog;
use App\Models\Tms\TmsMaintenanceLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_log_crud_and_vehicle_status_sync(): void
    {
        $factory = Factory::create(['name' => 'Test Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Admin',
            'permissions' => ['tms.maintenance.view', 'tms.maintenance.manage'],
        ]);

        $user = User::create([
            'name'       => 'Admin',
            'email'      => 'maint@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'         => $factory->id,
            'name'               => 'Hiace',
            'reg_number'         => 'DHK-5678',
            'type'               => 'own',
            'fuel_type'          => 'petrol',
            'passenger_capacity' => 8,
            'status'             => 'available',
        ]);

        $this->actingAs($user)
            ->post(route('admin.tms.maintenance.store'), [
                'factory_id'   => $factory->id,
                'vehicle_id'   => $vehicle->id,
                'service_date' => '2026-06-01',
                'service_type' => 'repair',
                'vendor_name'  => 'Auto Care',
                'labor_cost'   => 500,
                'paid_by'      => 'company',
                'status'       => 'open',
                'parts'        => [
                    ['part_name' => 'Oil Filter', 'quantity' => 1, 'unit_price' => 300],
                    ['part_name' => 'Engine Oil', 'quantity' => 4, 'unit_price' => 250],
                ],
            ])
            ->assertRedirect(route('admin.tms.maintenance.index'));

        $log = TmsMaintenanceLog::first();

        $this->assertNotNull($log);
        $this->assertEquals(1300.0, (float) $log->parts_cost);
        $this->assertEquals(1800.0, (float) $log->total_cost);
        $this->assertEquals('maintenance', $vehicle->fresh()->status);
        $this->assertCount(2, $log->parts);

        $this->actingAs($user)
            ->put(route('admin.tms.maintenance.update', $log), [
                'factory_id'   => $factory->id,
                'vehicle_id'   => $vehicle->id,
                'service_date' => '2026-06-01',
                'service_type' => 'repair',
                'vendor_name'  => 'Auto Care',
                'labor_cost'   => 500,
                'paid_by'      => 'company',
                'status'       => 'closed',
                'parts'        => [
                    ['part_name' => 'Oil Filter', 'quantity' => 1, 'unit_price' => 300],
                ],
            ])
            ->assertRedirect(route('admin.tms.maintenance.index'));

        $this->assertEquals('available', $vehicle->fresh()->status);
        $this->assertEquals(800.0, (float) $log->fresh()->total_cost);
    }

    public function test_maintenance_log_accepts_blank_labor_cost(): void
    {
        $factory = Factory::create(['name' => 'Test Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Admin',
            'permissions' => ['tms.maintenance.view', 'tms.maintenance.manage'],
        ]);

        $user = User::create([
            'name'       => 'Admin',
            'email'      => 'maint-blank@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'         => $factory->id,
            'name'               => 'Hiace',
            'reg_number'         => 'DHK-9999',
            'type'               => 'own',
            'fuel_type'          => 'petrol',
            'passenger_capacity' => 8,
            'status'             => 'available',
        ]);

        $this->actingAs($user)
            ->post(route('admin.tms.maintenance.store'), [
                'factory_id'   => $factory->id,
                'vehicle_id'   => $vehicle->id,
                'service_date' => '2026-06-01',
                'service_type' => 'repair',
                'paid_by'      => 'company',
                'status'       => 'open',
                'labor_cost'   => '',
            ])
            ->assertRedirect(route('admin.tms.maintenance.index'));

        $this->assertSame(0.0, (float) TmsMaintenanceLog::firstOrFail()->labor_cost);
    }

    public function test_fleet_cost_report_summarizes_costs(): void
    {
        $factory = Factory::create(['name' => 'Test Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Reports',
            'permissions' => ['tms.reports.view'],
        ]);

        $user = User::create([
            'name'       => 'Reporter',
            'email'      => 'reports@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'         => $factory->id,
            'name'               => 'Bus',
            'reg_number'         => 'DHK-0001',
            'type'               => 'own',
            'fuel_type'          => 'diesel',
            'passenger_capacity' => 20,
            'status'             => 'available',
        ]);

        TmsFuelLog::create([
            'factory_id' => $factory->id,
            'vehicle_id' => $vehicle->id,
            'fuel_type'  => 'diesel',
            'quantity'   => 50,
            'unit'       => 'litre',
            'unit_price' => 10,
            'amount'     => 500,
            'paid_by'    => 'company',
            'created_by' => $user->id,
        ]);

        TmsMaintenanceLog::create([
            'factory_id'   => $factory->id,
            'vehicle_id'   => $vehicle->id,
            'service_date' => '2026-06-15',
            'service_type' => 'routine',
            'labor_cost'   => 200,
            'parts_cost'   => 300,
            'total_cost'   => 500,
            'paid_by'      => 'company',
            'status'       => 'closed',
            'created_by'   => $user->id,
            'updated_by'   => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.tms.reports.index', ['tab' => 'fleet_cost']))
            ->assertOk()
            ->assertSee('Fleet Cost')
            ->assertSee('1,000.00');
    }
}
