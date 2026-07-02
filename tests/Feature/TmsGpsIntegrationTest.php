<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\Shift;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsGpsPosition;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsGpsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private EmployeePortalUser $driverPortal;

    private TmsVehicle $vehicle;

    private TmsTripLog $trip;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-24 10:00:00');

        config(['tms.gps_api_token' => 'test-gps-token-secret']);

        $this->factory = Factory::create(['name' => 'GPS Factory', 'is_active' => true]);

        TmsSetting::create(array_merge(
            TmsSetting::defaultValues(),
            [
                'factory_id'           => $this->factory->id,
                'gps_tracking_enabled' => true,
                'gps_provider'         => 'browser',
            ]
        ));

        $shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Day',
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $driverEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'GPS-D001',
            'name'          => 'GPS Driver',
            'status'        => 'active',
        ]);

        $this->driverPortal = EmployeePortalUser::create([
            'employee_id' => $driverEmployee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $this->vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'GPS Van',
            'reg_number'         => 'GPS-100',
            'type'               => 'own',
            'passenger_capacity' => 8,
            'status'             => 'available',
        ]);

        $driver = TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $driverEmployee->id,
            'default_vehicle_id' => $this->vehicle->id,
            'status'             => 'active',
        ]);

        $requester = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'GPS-E001',
            'name'          => 'Requester',
            'status'        => 'active',
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => now()->addHour(),
            'purpose'            => 'Trip',
            'passenger_count'    => 1,
            'status'             => 'approved',
            'vehicle_id'         => $this->vehicle->id,
            'driver_id'          => $driver->id,
        ]);

        $this->trip = TmsTripLog::create([
            'factory_id'            => $this->factory->id,
            'transport_request_id'  => $transportRequest->id,
            'vehicle_id'            => $this->vehicle->id,
            'driver_id'             => $driver->id,
            'trip_status'           => 'not_started',
        ]);
    }

    public function test_device_api_rejects_missing_token(): void
    {
        TmsSetting::where('factory_id', $this->factory->id)->update(['gps_provider' => 'device_api']);

        $this->postJson(route('api.tms.gps.positions.store'), [
            'vehicle_id' => $this->vehicle->id,
            'latitude'   => 23.8103,
            'longitude'  => 90.4125,
        ])->assertUnauthorized();
    }

    public function test_device_api_records_position_with_token(): void
    {
        TmsSetting::where('factory_id', $this->factory->id)->update(['gps_provider' => 'device_api']);

        $this->withToken('test-gps-token-secret')
            ->postJson(route('api.tms.gps.positions.store'), [
                'vehicle_id'  => $this->vehicle->id,
                'trip_log_id' => $this->trip->id,
                'latitude'    => 23.8103,
                'longitude'   => 90.4125,
                'speed_kmh'   => 42.5,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $pos = TmsGpsPosition::first();
        $this->assertNotNull($pos);
        $this->assertSame('device_api', $pos->source);
        $this->assertSame($this->trip->id, $pos->trip_log_id);
    }

    public function test_driver_trip_start_records_browser_gps(): void
    {
        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.trips.start', $this->trip), [
                'start_km'   => 1000,
                'latitude'   => 23.7808,
                'longitude'  => 90.2792,
                'accuracy_m' => 12.5,
            ])
            ->assertRedirect(route('employee.transport.trips'));

        $this->trip->refresh();
        $this->assertSame('in_progress', $this->trip->trip_status);

        $pos = TmsGpsPosition::first();
        $this->assertNotNull($pos);
        $this->assertSame('browser_start', $pos->source);
        $this->assertEqualsWithDelta(23.7808, (float) $pos->latitude, 0.0001);
    }

    public function test_driver_trip_end_records_browser_gps(): void
    {
        $this->trip->update([
            'trip_status'   => 'in_progress',
            'duty_start_at' => now()->subHour(),
            'start_km'      => 1000,
        ]);

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.trips.end', $this->trip), [
                'end_km'     => 1025,
                'latitude'   => 23.7925,
                'longitude'  => 90.4078,
                'accuracy_m' => 8,
            ])
            ->assertRedirect(route('employee.transport.trips'));

        $pos = TmsGpsPosition::where('source', 'browser_end')->first();
        $this->assertNotNull($pos);
        $this->assertEqualsWithDelta(23.7925, (float) $pos->latitude, 0.0001);
    }
}
