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
                'tms.trips.manage',
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

    public function test_authority_can_approve_with_driver_and_vehicle_from_another_unit(): void
    {
        $crossUnitRole = Role::create([
            'name'        => 'TMS Cross Unit Authority',
            'permissions' => [
                'tms.dashboard.view',
                'tms.requests.view',
                'tms.requests.approve',
                'tms.trips.view',
            ],
        ]);

        $crossUnitAdmin = User::create([
            'name'       => 'Cross Unit Admin',
            'email'      => 'tms-cross@test.com',
            'password'   => 'password',
            'role_id'    => $crossUnitRole->id,
            'factory_id' => null,
        ]);

        $otherEmployee = Employee::create([
            'factory_id'    => $this->otherFactory->id,
            'employee_code' => 'TMS-DRV-OTHER',
            'name'          => 'Other Unit Driver',
            'status'        => 'active',
        ]);

        $otherVehicle = TmsVehicle::create([
            'factory_id'         => $this->otherFactory->id,
            'name'               => 'Pajero Jeep',
            'reg_number'         => 'DM-GHA-02-0005',
            'type'               => 'own',
            'fuel_type'          => 'octane',
            'passenger_capacity' => 7,
            'status'             => 'available',
        ]);

        $otherDriver = TmsDriver::create([
            'factory_id'         => $this->otherFactory->id,
            'employee_id'        => $otherEmployee->id,
            'default_vehicle_id' => $otherVehicle->id,
            'status'             => 'active',
            'ot_rate'            => 100,
        ]);
        $otherDriver->syncAssignedVehicles([$otherVehicle->id], $otherVehicle->id);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->requester->id,
            'pickup_location'    => 'Head Office',
            'destination_custom' => 'Uttara',
            'pickup_at'          => '2026-06-18 23:51:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($crossUnitAdmin)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $otherDriver->id,
                'vehicle_id'  => $otherVehicle->id,
            ])
            ->assertRedirect();

        $transportRequest->refresh();
        $this->assertSame('approved', $transportRequest->status);
        $this->assertSame($otherDriver->id, $transportRequest->driver_id);
        $this->assertSame($otherVehicle->id, $transportRequest->vehicle_id);
    }

    public function test_driver_from_another_unit_can_start_assigned_trip(): void
    {
        $otherEmployee = Employee::create([
            'factory_id'    => $this->otherFactory->id,
            'employee_code' => 'TMS-DRV-CROSS',
            'name'          => 'Cross Unit Driver Emp',
            'status'        => 'active',
        ]);

        // Driver record unit differs from employee unit (common after cross-unit assignment).
        $otherDriver = TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $otherEmployee->id,
            'default_vehicle_id' => $this->vehicle->id,
            'status'             => 'active',
            'ot_rate'            => 100,
        ]);
        $otherDriver->syncAssignedVehicles([$this->vehicle->id], $this->vehicle->id);

        $driverPortal = EmployeePortalUser::create([
            'employee_id' => $otherEmployee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->requester->id,
            'pickup_location'    => 'Head Office',
            'destination_custom' => 'Uttara',
            'pickup_at'          => '2026-06-18 23:51:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $otherDriver->id,
                'vehicle_id'  => $this->vehicle->id,
            ])
            ->assertRedirect();

        $trip = TmsTripLog::first();
        $this->assertNotNull($trip);

        $this->actingAs($driverPortal, 'employee')
            ->post(route('employee.transport.trips.start', $trip))
            ->assertRedirect(route('employee.transport.trips'));

        $this->assertSame('in_progress', $trip->fresh()->trip_status);
    }

    public function test_driver_receives_trip_assigned_notification_on_approve(): void
    {
        \App\Models\AppSetting::query()->delete();
        \App\Models\AppSetting::create(array_merge(
            \App\Models\AppSetting::defaults(),
            [
                'notify_popup_enabled' => true,
                'notify_popup_tms' => true,
                'notify_popup_tms_request_approved' => true,
            ]
        ));

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->requester->id,
            'pickup_location'    => 'Head Office',
            'destination_custom' => 'Uttara',
            'pickup_at'          => '2026-06-18 23:51:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->driver->syncAssignedVehicles([$this->vehicle->id], $this->vehicle->id);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $this->driver->id,
                'vehicle_id'  => $this->vehicle->id,
            ])
            ->assertRedirect();

        $this->assertTrue(
            $this->driverPortal->fresh()->notifications()
                ->where('data->type', 'tms_trip_assigned')
                ->exists()
        );
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

    public function test_employee_can_edit_pending_request(): void
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

        $this->actingAs($this->requesterPortal, 'employee')
            ->put(route('employee.transport.requests.update', $transportRequest), [
                'pickup_location'    => 'Main Gate Updated',
                'destination_custom' => 'Airport',
                'pickup_at'          => '2026-06-17 14:00',
                'purpose'            => 'Updated purpose',
                'passenger_count'    => 2,
            ])
            ->assertRedirect(route('employee.transport.requests.show', $transportRequest));

        $transportRequest->refresh();
        $this->assertSame('Main Gate Updated', $transportRequest->pickup_location);
        $this->assertSame(2, $transportRequest->passenger_count);
        $this->assertSame('pending', $transportRequest->status);
    }

    public function test_admin_can_cancel_approved_request_before_trip_starts(): void
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

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.cancel', $transportRequest), [
                'reason' => 'No longer needed',
            ])
            ->assertRedirect(route('admin.tms.requests.show', $transportRequest));

        $transportRequest->refresh();
        $this->assertSame('cancelled', $transportRequest->status);
        $this->assertNull($transportRequest->trip_log_id);
        $this->assertNull(TmsTripLog::find($tripId));
    }

    public function test_admin_can_reassign_driver_before_trip_starts(): void
    {
        $secondDriverEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $this->requester->shift_id,
            'employee_code' => 'TMS-D002',
            'name'          => 'Driver Two',
            'status'        => 'active',
        ]);

        $secondDriver = TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $secondDriverEmployee->id,
            'default_vehicle_id' => $this->smallVehicle->id,
            'ot_rate'            => 120,
            'is_overtime_active' => true,
            'status'             => 'active',
        ]);

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

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.reassign', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $secondDriver->id,
            ])
            ->assertRedirect();

        $transportRequest->refresh();
        $trip = TmsTripLog::first();

        $this->assertSame($secondDriver->id, $transportRequest->driver_id);
        $this->assertSame($this->smallVehicle->id, $transportRequest->vehicle_id);
        $this->assertSame($secondDriver->id, $trip->driver_id);
        $this->assertSame($this->smallVehicle->id, $trip->vehicle_id);
    }

    public function test_admin_can_abort_in_progress_trip(): void
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

        $this->actingAs($this->authority)
            ->post(route('admin.tms.trips.abort', $trip), [
                'reason' => 'Vehicle breakdown',
            ])
            ->assertRedirect(route('admin.tms.trips.show', $trip));

        $transportRequest->refresh();
        $trip->refresh();

        $this->assertSame('cancelled', $transportRequest->status);
        $this->assertSame('completed', $trip->trip_status);
        $this->assertSame('available', $this->vehicle->fresh()->status);
    }

    public function test_request_show_displays_status_history(): void
    {
        $this->actingAs($this->requesterPortal, 'employee')
            ->post(route('employee.transport.requests.store'), [
                'pickup_location'    => 'Factory Gate',
                'destination_custom' => 'Airport',
                'pickup_at'          => '2026-06-17 14:00',
                'purpose'            => 'Client visit',
                'passenger_count'    => 1,
            ]);

        $transportRequest = TmsTransportRequest::first();

        $this->actingAs($this->authority)
            ->get(route('admin.tms.requests.show', $transportRequest))
            ->assertOk()
            ->assertSee('Status History')
            ->assertSee('Submitted');
    }
}
