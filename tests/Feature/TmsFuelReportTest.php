<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\Tms\TmsFuelLog;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsFuelReportTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $user;

    private TmsVehicle $vehicleA;

    private TmsVehicle $vehicleB;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');

        $this->factory = Factory::create(['name' => 'Fuel Report Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Fuel Report Admin',
            'permissions' => ['tms.reports.view'],
        ]);

        $this->user = User::create([
            'name'       => 'Fuel Report User',
            'email'      => 'fuel-report@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);

        $this->vehicleA = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Pickup A',
            'reg_number'         => 'DHK-A',
            'type'               => 'own',
            'fuel_type'          => 'diesel',
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);

        $this->vehicleB = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Pickup B',
            'reg_number'         => 'DHK-B',
            'type'               => 'own',
            'fuel_type'          => 'octane',
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);
    }

    public function test_fuel_report_shows_summary_trip_link_and_vehicle_filter(): void
    {
        $shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Day',
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'FR-001',
            'name'          => 'Fuel Staff',
            'status'        => 'active',
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $employee->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-15 10:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'completed',
        ]);

        $trip = TmsTripLog::create([
            'transport_request_id' => $transportRequest->id,
            'factory_id'           => $this->factory->id,
            'vehicle_id'           => $this->vehicleA->id,
            'total_passengers'     => 1,
            'trip_status'          => 'completed',
        ]);

        TmsFuelLog::create([
            'factory_id'   => $this->factory->id,
            'vehicle_id'   => $this->vehicleA->id,
            'trip_log_id'  => $trip->id,
            'fuel_type'    => 'diesel',
            'quantity'     => 10,
            'unit'         => 'litre',
            'unit_price'   => 120,
            'amount'       => 1200,
            'paid_by'      => 'company',
            'created_by'   => $this->user->id,
            'created_at'   => '2026-06-10 09:00:00',
        ]);

        TmsFuelLog::create([
            'factory_id'   => $this->factory->id,
            'vehicle_id'   => $this->vehicleB->id,
            'fuel_type'    => 'octane',
            'quantity'     => 5,
            'unit'         => 'litre',
            'unit_price'   => 130,
            'amount'       => 650,
            'paid_by'      => 'rental_party',
            'created_by'   => $this->user->id,
            'created_at'   => '2026-06-12 09:00:00',
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.reports.index', [
                'tab'  => 'fuel',
                'from' => '2026-06-01',
                'to'   => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Total Amount')
            ->assertSee('1,850.00')
            ->assertSee('15')
            ->assertSee('Diesel')
            ->assertSee(route('admin.tms.trips.show', $trip->id), false);

        $this->actingAs($this->user)
            ->get(route('admin.tms.reports.index', [
                'tab'        => 'fuel',
                'vehicle_id' => $this->vehicleA->id,
                'from'       => '2026-06-01',
                'to'         => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('1 entry')
            ->assertSee('1,200.00')
            ->assertDontSee('650.00');
    }

    public function test_fuel_report_by_vehicle_groups_rows(): void
    {
        TmsFuelLog::create([
            'factory_id' => $this->factory->id,
            'vehicle_id' => $this->vehicleA->id,
            'fuel_type'  => 'diesel',
            'quantity'   => 10,
            'unit'       => 'litre',
            'unit_price' => 120,
            'amount'     => 1200,
            'paid_by'    => 'company',
            'created_by' => $this->user->id,
            'created_at' => '2026-06-10 09:00:00',
        ]);

        TmsFuelLog::create([
            'factory_id' => $this->factory->id,
            'vehicle_id' => $this->vehicleA->id,
            'fuel_type'  => 'diesel',
            'quantity'   => 8,
            'unit'       => 'litre',
            'unit_price' => 120,
            'amount'     => 960,
            'paid_by'    => 'company',
            'created_by' => $this->user->id,
            'created_at' => '2026-06-11 09:00:00',
        ]);

        TmsFuelLog::create([
            'factory_id' => $this->factory->id,
            'vehicle_id' => $this->vehicleB->id,
            'fuel_type'  => 'octane',
            'quantity'   => 5,
            'unit'       => 'litre',
            'unit_price' => 130,
            'amount'     => 650,
            'paid_by'    => 'rental_party',
            'created_by' => $this->user->id,
            'created_at' => '2026-06-12 09:00:00',
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.reports.index', [
                'tab'       => 'fuel',
                'fuel_view' => 'by_vehicle',
                'from'      => '2026-06-01',
                'to'        => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('By Vehicle', false)
            ->assertSee('Pickup A')
            ->assertSee('Pickup B')
            ->assertSee('2,160.00')
            ->assertSee('View entries');
    }

    public function test_fuel_by_vehicle_csv_export(): void
    {
        TmsFuelLog::create([
            'factory_id' => $this->factory->id,
            'vehicle_id' => $this->vehicleA->id,
            'fuel_type'  => 'diesel',
            'quantity'   => 10,
            'unit'       => 'litre',
            'unit_price' => 120,
            'amount'     => 1200,
            'paid_by'    => 'company',
            'created_by' => $this->user->id,
            'created_at' => '2026-06-10 09:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.tms.reports.export', [
                'report' => 'fuel_by_vehicle',
                'from'   => '2026-06-01',
                'to'     => '2026-06-30',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('Vehicle', $response->streamedContent());
        $this->assertStringContainsString('Pickup A', $response->streamedContent());
        $this->assertStringContainsString('1200', $response->streamedContent());
    }
}
