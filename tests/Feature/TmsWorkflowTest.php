<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private Factory $otherFactory;

    private User $authority;

    private Employee $requester;

    private Employee $requester2;

    private Employee $driverEmployee;

    private EmployeePortalUser $requesterPortal;

    private EmployeePortalUser $driverPortal;

    private TmsVehicle $vehicle;

    private TmsVehicle $smallVehicle;

    private TmsDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');

        $this->factory = Factory::create(['name' => 'TMS Factory', 'is_active' => true]);
        $this->otherFactory = Factory::create(['name' => 'Other Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Authority',
            'permissions' => [
                'tms.dashboard.view',
                'tms.requests.view',
                'tms.requests.approve',
                'tms.vehicles.view',
                'tms.drivers.view',
                'tms.trips.view',
            ],
        ]);

        $this->authority = User::create([
            'name'       => 'Transport Admin',
            'email'      => 'tms-auth@test.com',
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

        $this->requester = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'TMS-E001',
            'name'          => 'Requester',
            'status'        => 'active',
        ]);

        $this->requester2 = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'TMS-E002',
            'name'          => 'Requester Two',
            'status'        => 'active',
        ]);

        $this->driverEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'TMS-D001',
            'name'          => 'Driver One',
            'status'        => 'active',
        ]);

        $this->requesterPortal = EmployeePortalUser::create([
            'employee_id' => $this->requester->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        EmployeePortalUser::create([
            'employee_id' => $this->requester2->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $this->driverPortal = EmployeePortalUser::create([
            'employee_id' => $this->driverEmployee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        TmsSetting::create([
            'factory_id'   => $this->factory->id,
            'office_start' => '09:00:00',
            'office_end'   => '17:00:00',
            'ot_basis'     => 'global_office_time',
        ]);

        $this->vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Hiace',
            'reg_number'         => 'DHK-1234',
            'type'               => 'own',
            'fuel_type'          => 'petrol',
            'passenger_capacity' => 8,
            'status'             => 'available',
        ]);

        $this->smallVehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Car',
            'reg_number'         => 'DHK-9999',
            'type'               => 'own',
            'fuel_type'          => 'petrol',
            'passenger_capacity' => 3,
            'status'             => 'available',
        ]);

        $this->driver = TmsDriver::create([
            'factory_id'          => $this->factory->id,
            'employee_id'         => $this->driverEmployee->id,
            'default_vehicle_id'  => $this->vehicle->id,
            'ot_rate'             => 150,
            'is_overtime_active'  => true,
            'status'              => 'active',
        ]);
    }

    public function test_employee_submits_request_and_authority_sees_pending(): void
    {
        $this->actingAs($this->requesterPortal, 'employee')
            ->post(route('employee.transport.requests.store'), [
                'pickup_location'    => 'Factory Gate',
                'destination_custom' => 'Airport',
                'pickup_at'          => '2026-06-17 14:00',
                'purpose'            => 'Client visit',
                'passenger_count'    => 1,
            ])
            ->assertRedirect(route('employee.transport.index'));

        $this->assertSame('pending', TmsTransportRequest::first()->status);

        $this->actingAs($this->authority)
            ->get(route('admin.tms.requests.index'))
            ->assertOk()
            ->assertSee('Requester');
    }

    public function test_authority_approves_request_and_assigns_driver_and_vehicle(): void
    {
        $this->actingAs($this->requesterPortal, 'employee')
            ->post(route('employee.transport.requests.store'), [
                'pickup_location'    => 'Main Gate',
                'destination_custom' => 'Gulshan',
                'pickup_at'          => '2026-06-18 09:30',
                'purpose'            => 'Bank work',
                'passenger_count'    => 2,
            ])
            ->assertRedirect(route('employee.transport.index'));

        $transportRequest = TmsTransportRequest::first();
        $this->assertSame('pending', $transportRequest->status);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $this->driver->id,
                'vehicle_id'  => $this->vehicle->id,
            ])
            ->assertRedirect(route('admin.tms.trips.show', TmsTripLog::first()));

        $transportRequest->refresh();
        $trip = TmsTripLog::first();

        $this->assertSame('approved', $transportRequest->status);
        $this->assertSame($this->driver->id, $transportRequest->driver_id);
        $this->assertSame($this->vehicle->id, $transportRequest->vehicle_id);
        $this->assertSame($trip->id, $transportRequest->trip_log_id);
        $this->assertSame($this->authority->id, $transportRequest->approved_by);
        $this->assertNotNull($transportRequest->approved_at);
        $this->assertSame($this->driver->id, $trip->driver_id);
        $this->assertSame($this->vehicle->id, $trip->vehicle_id);
        $this->assertSame(2, $trip->total_passengers);
        $this->assertSame('not_started', $trip->trip_status);

        $this->actingAs($this->authority)
            ->get(route('admin.tms.requests.show', $transportRequest))
            ->assertOk()
            ->assertSee('Driver One')
            ->assertSee('Hiace');
    }

    public function test_full_workflow_with_ot_calculation_without_driver_km(): void
    {
        $this->actingAs($this->requesterPortal, 'employee')
            ->post(route('employee.transport.requests.store'), [
                'pickup_location'    => 'Factory',
                'destination_custom' => 'Dhaka',
                'pickup_at'          => '2026-06-17 10:00',
                'purpose'            => 'Meeting',
                'passenger_count'    => 1,
            ]);

        $transportRequest = TmsTransportRequest::first();

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $this->driver->id,
            ])
            ->assertRedirect();

        $trip = TmsTripLog::first();

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.trips.start', $trip))
            ->assertRedirect(route('employee.transport.trips'));

        Carbon::setTestNow('2026-06-17 21:00:00');

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.trips.end', $trip))
            ->assertRedirect(route('employee.transport.trips'));

        $trip->refresh();
        $transportRequest->refresh();

        $this->assertSame('completed', $transportRequest->status);
        $this->assertNull($trip->start_km);
        $this->assertNull($trip->end_km);
        $this->assertSame(0.0, (float) $trip->ot_hours);
        $this->assertSame(120.0, (float) $trip->total_driver_pay);
        $this->assertSame(120.0, (float) $trip->night_bill_amount);
        $this->assertSame('night_bill', $trip->bill_type);
    }

    public function test_merge_three_requests_into_one_trip_on_three_seater(): void
    {
        $this->driver->update(['default_vehicle_id' => $this->smallVehicle->id]);

        $ids = [];
        foreach ([$this->requester, $this->requester2, $this->requester] as $i => $emp) {
            $ids[] = TmsTransportRequest::create([
                'factory_id'         => $this->factory->id,
                'employee_id'        => $emp->id,
                'pickup_location'    => 'Gate',
                'destination_custom' => 'Airport',
                'pickup_at'          => '2026-06-17 10:00',
                'purpose'            => 'Trip ' . $i,
                'passenger_count'    => 1,
                'status'             => 'pending',
            ])->id;
        }

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.merge'), [
                'request_ids' => $ids,
                'driver_type' => 'company',
                'driver_id'   => $this->driver->id,
            ])
            ->assertRedirect();

        $this->assertSame(1, TmsTripLog::count());
        $trip = TmsTripLog::first();
        $this->assertSame(3, $trip->total_passengers);
        $this->assertSame(3, TmsTransportRequest::where('trip_log_id', $trip->id)->count());
        $this->assertSame($this->smallVehicle->id, $trip->vehicle_id);
    }

    public function test_merge_blocked_when_exceeding_vehicle_capacity(): void
    {
        $this->driver->update(['default_vehicle_id' => $this->smallVehicle->id]);

        $ids = [];
        for ($i = 0; $i < 4; $i++) {
            $ids[] = TmsTransportRequest::create([
                'factory_id'         => $this->factory->id,
                'employee_id'        => $this->requester->id,
                'pickup_location'    => 'Gate',
                'destination_custom' => 'Airport',
                'pickup_at'          => '2026-06-17 10:00',
                'purpose'            => 'Trip',
                'passenger_count'    => 1,
                'status'             => 'pending',
            ])->id;
        }

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.merge'), [
                'request_ids' => $ids,
                'driver_type' => 'company',
                'driver_id'   => $this->driver->id,
            ])
            ->assertSessionHasErrors('vehicle_id');
    }

    public function test_employee_can_cancel_pending_request(): void
    {
        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => now()->addDay(),
            'purpose'            => 'Test',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->requesterPortal, 'employee')
            ->post(route('employee.transport.requests.cancel', $transportRequest))
            ->assertRedirect(route('employee.transport.index'));

        $this->assertSame('cancelled', $transportRequest->fresh()->status);
    }

    public function test_employee_can_cancel_approved_request_before_trip_starts(): void
    {
        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-17 10:00',
            'purpose'            => 'Test',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $this->driver->id,
            ]);

        $transportRequest->refresh();
        $tripId = $transportRequest->trip_log_id;
        $this->assertSame('approved', $transportRequest->status);
        $this->assertNotNull($tripId);

        $this->actingAs($this->requesterPortal, 'employee')
            ->post(route('employee.transport.requests.cancel', $transportRequest))
            ->assertRedirect(route('employee.transport.index'));

        $transportRequest->refresh();
        $this->assertSame('cancelled', $transportRequest->status);
        $this->assertNull($transportRequest->trip_log_id);
        $this->assertNull(TmsTripLog::find($tripId));
    }

    public function test_employee_cannot_cancel_after_trip_starts(): void
    {
        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-17 10:00',
            'purpose'            => 'Test',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $this->driver->id,
            ]);

        $trip = TmsTripLog::first();

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.trips.start', $trip));

        $this->actingAs($this->requesterPortal, 'employee')
            ->post(route('employee.transport.requests.cancel', $transportRequest))
            ->assertSessionHasErrors('status');

        $this->assertSame('in_progress', $transportRequest->fresh()->status);
    }

    public function test_non_driver_cannot_access_trips(): void
    {
        $this->actingAs($this->requesterPortal, 'employee')
            ->get(route('employee.transport.trips'))
            ->assertForbidden();
    }

    public function test_factory_scoped_user_cannot_view_other_factory_request(): void
    {
        $otherRequest = TmsTransportRequest::create([
            'factory_id'         => $this->otherFactory->id,
            'employee_id'        => Employee::create([
                'factory_id'    => $this->otherFactory->id,
                'employee_code' => 'OTH-001',
                'name'          => 'Other Worker',
                'status'        => 'active',
            ])->id,
            'pickup_location'    => 'Other',
            'destination_custom' => 'Place',
            'pickup_at'          => now()->addDay(),
            'purpose'            => 'Test',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->authority)
            ->get(route('admin.tms.requests.show', $otherRequest))
            ->assertForbidden();
    }

    public function test_ot_zero_when_driver_overtime_inactive(): void
    {
        $this->driver->update(['is_overtime_active' => false]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-17 10:00',
            'purpose'            => 'Test',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $this->driver->id,
            ]);

        $trip = TmsTripLog::first();

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.trips.start', $trip));

        Carbon::setTestNow('2026-06-17 21:00:00');

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.trips.end', $trip));

        $trip->refresh();
        $this->assertSame(0.0, (float) $trip->ot_hours);
        $this->assertSame(0.0, (float) $trip->ot_amount);
    }
}
