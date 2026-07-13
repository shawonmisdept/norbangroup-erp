<?php

namespace Tests\Unit;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Shift;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsVehicle;
use App\Support\TmsDriverVehiclePivot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TmsDriverVehiclePivotTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_driver_names_falls_back_when_pivot_table_missing(): void
    {
        Schema::dropIfExists('tms_driver_vehicles');

        $factory = Factory::create(['name' => 'Pivot Test', 'is_active' => true]);
        $shift = Shift::create([
            'factory_id'    => $factory->id,
            'name'          => 'Day',
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'PT-001',
            'name'          => 'Pivot Driver',
            'status'        => 'active',
        ]);
        $vehicle = TmsVehicle::create([
            'factory_id'         => $factory->id,
            'name'               => 'Test Van',
            'reg_number'         => 'PT-1',
            'type'               => 'own',
            'fuel_type'          => 'diesel',
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);
        $driver = TmsDriver::create([
            'factory_id'         => $factory->id,
            'employee_id'        => $employee->id,
            'default_vehicle_id' => $vehicle->id,
            'ot_rate'            => 100,
            'status'             => 'active',
        ]);

        $vehicle->update(['primary_driver_id' => $driver->id]);

        $this->assertFalse(TmsDriverVehiclePivot::available());
        $this->assertSame('Pivot Driver', $vehicle->fresh()->assignedDriverNames());
    }
}
