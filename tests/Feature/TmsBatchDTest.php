<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsDriverOtRateLog;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsBatchDTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $user;

    private Department $sewingDept;

    private Department $hrDept;

    private Employee $sewingEmployee;

    private Employee $hrEmployee;

    private Employee $unassignedEmployee;

    private TmsVehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');

        $this->factory = Factory::create(['name' => 'Batch D Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Batch D',
            'permissions' => ['tms.reports.view', 'tms.drivers.view', 'tms.drivers.manage'],
        ]);

        $this->user = User::create([
            'name'       => 'Batch D Admin',
            'email'      => 'batchd@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);

        $shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Day',
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $this->sewingDept = Department::create(['factory_id' => $this->factory->id, 'name' => 'Sewing', 'is_active' => true]);
        $this->hrDept = Department::create(['factory_id' => $this->factory->id, 'name' => 'HR', 'is_active' => true]);

        $this->sewingEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'department_id' => $this->sewingDept->id,
            'employee_code' => 'BD-E001',
            'name'          => 'Sewing Staff',
            'status'        => 'active',
        ]);

        $this->hrEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'department_id' => $this->hrDept->id,
            'employee_code' => 'BD-E002',
            'name'          => 'HR Staff',
            'status'        => 'active',
        ]);

        $this->unassignedEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'BD-E003',
            'name'          => 'No Dept Staff',
            'status'        => 'active',
        ]);

        $this->vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Car 1',
            'reg_number'         => 'BD-1',
            'type'               => 'own',
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);
    }

    public function test_requests_by_department_report_aggregates_counts(): void
    {
        $this->seedRequest($this->sewingEmployee, 'pending', 2);
        $this->seedRequest($this->sewingEmployee, 'completed', 3);
        $this->seedRequest($this->hrEmployee, 'approved', 1);
        $this->seedRequest($this->unassignedEmployee, 'rejected', 1);

        $this->actingAs($this->user)
            ->get(route('admin.tms.reports.index', [
                'tab'  => 'requests_by_department',
                'from' => '2026-06-01',
                'to'   => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Requests by Dept')
            ->assertSee('Sewing')
            ->assertSee('HR')
            ->assertSee('Unassigned');
    }

    public function test_requests_by_department_filter_limits_rows(): void
    {
        $this->seedRequest($this->sewingEmployee, 'pending', 2);
        $this->seedRequest($this->hrEmployee, 'pending', 1);

        $this->actingAs($this->user)
            ->get(route('admin.tms.reports.index', [
                'tab'           => 'requests_by_department',
                'department_id' => $this->sewingDept->id,
            ]))
            ->assertOk()
            ->assertSee('Sewing')
            ->assertDontSee('>HR<');
    }

    public function test_requests_by_department_csv_export(): void
    {
        $this->seedRequest($this->sewingEmployee, 'pending', 2);

        $response = $this->actingAs($this->user)
            ->get(route('admin.tms.reports.export', [
                'report' => 'requests_by_department',
                'from'   => '2026-06-01',
                'to'     => '2026-06-30',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('Department', $response->streamedContent());
        $this->assertStringContainsString('Sewing', $response->streamedContent());
    }

    public function test_driver_create_records_initial_ot_rate_log(): void
    {
        $driverEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'BD-DRV',
            'name'          => 'Driver One',
            'status'        => 'active',
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.tms.drivers.store'), [
                'factory_id'             => $this->factory->id,
                'employee_id'            => $driverEmployee->id,
                'default_vehicle_id'     => $this->vehicle->id,
                'ot_rate'                => 120,
                'ot_rate_effective_from' => '2026-06-01',
                'is_overtime_active'     => 1,
                'status'                 => 'active',
            ])
            ->assertRedirect(route('admin.tms.drivers.index'));

        $driver = TmsDriver::first();
        $this->assertNotNull($driver);
        $this->assertSame('2026-06-01', $driver->ot_rate_effective_from->toDateString());

        $log = TmsDriverOtRateLog::first();
        $this->assertNotNull($log);
        $this->assertSame(120.0, (float) $log->ot_rate);
        $this->assertSame('2026-06-01', $log->effective_from->toDateString());
        $this->assertSame($this->user->id, $log->recorded_by);
    }

    public function test_driver_update_logs_ot_rate_change_only_when_rules_change(): void
    {
        $driverEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'BD-DRV2',
            'name'          => 'Driver Two',
            'status'        => 'active',
        ]);

        $driver = TmsDriver::create([
            'factory_id'             => $this->factory->id,
            'employee_id'            => $driverEmployee->id,
            'default_vehicle_id'     => $this->vehicle->id,
            'ot_rate'                => 100,
            'ot_rate_effective_from' => '2026-06-01',
            'is_overtime_active'     => true,
            'status'                 => 'active',
        ]);

        TmsDriverOtRateLog::create([
            'driver_id'          => $driver->id,
            'ot_rate'            => 100,
            'effective_from'     => '2026-06-01',
            'is_overtime_active' => true,
            'recorded_by'        => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->put(route('admin.tms.drivers.update', $driver), [
                'factory_id'             => $this->factory->id,
                'employee_id'            => $driverEmployee->id,
                'default_vehicle_id'     => $this->vehicle->id,
                'license_number'         => 'LIC-123',
                'ot_rate'                => 100,
                'ot_rate_effective_from' => '2026-06-01',
                'is_overtime_active'     => 1,
                'status'                 => 'active',
            ])
            ->assertRedirect(route('admin.tms.drivers.index'));

        $this->assertSame(1, TmsDriverOtRateLog::where('driver_id', $driver->id)->count());

        $this->actingAs($this->user)
            ->put(route('admin.tms.drivers.update', $driver), [
                'factory_id'             => $this->factory->id,
                'employee_id'            => $driverEmployee->id,
                'default_vehicle_id'     => $this->vehicle->id,
                'license_number'         => 'LIC-123',
                'ot_rate'                => 150,
                'ot_rate_effective_from' => '2026-07-01',
                'is_overtime_active'     => 1,
                'status'                 => 'active',
            ])
            ->assertRedirect(route('admin.tms.drivers.index'));

        $this->assertSame(2, TmsDriverOtRateLog::where('driver_id', $driver->id)->count());

        $latest = TmsDriverOtRateLog::where('driver_id', $driver->id)->latest('id')->first();
        $this->assertSame(150.0, (float) $latest->ot_rate);
        $this->assertSame('2026-07-01', $latest->effective_from->toDateString());
    }

    public function test_driver_show_displays_ot_rate_history(): void
    {
        $driverEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'BD-DRV3',
            'name'          => 'Driver Three',
            'status'        => 'active',
        ]);

        $driver = TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $driverEmployee->id,
            'default_vehicle_id' => $this->vehicle->id,
            'ot_rate'            => 200,
            'is_overtime_active' => true,
            'status'             => 'active',
        ]);

        TmsDriverOtRateLog::create([
            'driver_id'          => $driver->id,
            'ot_rate'            => 200,
            'effective_from'     => '2026-06-01',
            'is_overtime_active' => true,
            'recorded_by'        => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.drivers.show', $driver))
            ->assertOk()
            ->assertSee('OT Rate History')
            ->assertSee('200.00')
            ->assertSee('01 Jun 2026');
    }

    private function seedRequest(Employee $employee, string $status, int $passengers): TmsTransportRequest
    {
        return TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $employee->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-15 10:00',
            'purpose'            => 'Visit',
            'passenger_count'    => $passengers,
            'status'             => $status,
        ]);
    }
}
