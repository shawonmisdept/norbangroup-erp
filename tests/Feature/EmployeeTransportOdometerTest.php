<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\Shift;
use App\Models\Tms\TmsDailyOdometerLog;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsVehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTransportOdometerTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private Employee $driverEmployee;

    private EmployeePortalUser $driverPortal;

    private TmsVehicle $vehicle;

    private TmsDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-24 08:30:00');

        $this->factory = Factory::create(['name' => 'Test Factory', 'is_active' => true]);

        $shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Day',
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'break_minutes' => 60,
        ]);

        $this->driverEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'DRV-001',
            'name'          => 'Driver Karim',
            'status'        => 'active',
        ]);

        $this->driverPortal = EmployeePortalUser::create([
            'employee_id' => $this->driverEmployee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $this->vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Hiace',
            'reg_number'         => 'DHK-2222',
            'type'               => 'own',
            'fuel_type'          => 'petrol',
            'passenger_capacity' => 8,
            'status'             => 'available',
            'last_odometer_km'   => 1000,
        ]);

        $this->driver = TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->driverEmployee->id,
            'default_vehicle_id' => $this->vehicle->id,
            'status'             => 'active',
        ]);

        $this->driver->syncAssignedVehicles([$this->vehicle->id], $this->vehicle->id);
    }

    public function test_driver_records_morning_km_and_sees_time_on_index(): void
    {
        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.odometer.morning'), [
                'vehicle_id' => $this->vehicle->id,
                'morning_km' => 1050,
            ])
            ->assertRedirect(route('employee.transport.odometer', ['vehicle_id' => $this->vehicle->id]));

        $log = TmsDailyOdometerLog::first();

        $this->assertNotNull($log);
        $this->assertSame(1050.0, (float) $log->morning_km);
        $this->assertSame('08:30 AM', $log->morningRecordedTime());
        $this->assertSame($this->driverEmployee->id, $log->morning_entered_by_employee);

        $this->actingAs($this->driverPortal, 'employee')
            ->get(route('employee.transport.odometer'))
            ->assertOk()
            ->assertSee('1,050.00')
            ->assertSee('08:30 AM')
            ->assertSee('Save Evening KM');
    }

    public function test_driver_records_evening_km_separately_and_sees_time(): void
    {
        Carbon::setTestNow('2026-06-24 18:15:00');

        $log = TmsDailyOdometerLog::create([
            'factory_id'                  => $this->factory->id,
            'vehicle_id'                  => $this->vehicle->id,
            'log_date'                    => '2026-06-24',
            'morning_km'                  => 1050,
            'morning_recorded_at'         => '2026-06-24 08:30:00',
            'morning_entered_by_employee' => $this->driverEmployee->id,
        ]);

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.odometer.evening', $log), [
                'evening_km' => 1120,
            ])
            ->assertRedirect(route('employee.transport.odometer', ['vehicle_id' => $this->vehicle->id]));

        $log->refresh();

        $this->assertSame(1120.0, (float) $log->evening_km);
        $this->assertSame('06:15 PM', $log->eveningRecordedTime());
        $this->assertSame(1120.0, (float) $this->vehicle->fresh()->last_odometer_km);

        $this->actingAs($this->driverPortal, 'employee')
            ->get(route('employee.transport.odometer'))
            ->assertOk()
            ->assertSee('1,120.00')
            ->assertSee('06:15 PM')
            ->assertSee('Today complete');
    }

    public function test_non_driver_cannot_access_odometer(): void
    {
        $employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $this->driverEmployee->shift_id,
            'employee_code' => 'EMP-002',
            'name'          => 'Regular Staff',
            'status'        => 'active',
        ]);

        $portal = EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $this->actingAs($portal, 'employee')
            ->get(route('employee.transport.odometer'))
            ->assertForbidden();
    }
}
