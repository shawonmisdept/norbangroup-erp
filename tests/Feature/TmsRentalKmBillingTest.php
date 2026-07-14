<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsRentalVendor;
use App\Models\Tms\TmsRentalVehicleCharge;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use App\Services\Tms\RentalChargeCalculator;
use App\Services\Tms\RentalRateResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsRentalKmBillingTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $authority;

    private Employee $driverEmployee;

    private EmployeePortalUser $driverPortal;

    private TmsDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');

        $this->factory = Factory::create(['name' => 'TMS Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Authority',
            'permissions' => [
                'tms.settings.view',
                'tms.settings.manage',
                'tms.rental_vendors.view',
                'tms.rental_vendors.manage',
                'tms.rental_charges.manage',
                'tms.requests.approve',
                'tms.trips.view',
            ],
        ]);

        $this->authority = User::create([
            'name'       => 'Transport Admin',
            'email'      => 'tms-rental@test.com',
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

        $this->driverEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'TMS-RD001',
            'name'          => 'Driver Rental',
            'status'        => 'active',
        ]);

        $this->driverPortal = EmployeePortalUser::create([
            'employee_id' => $this->driverEmployee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        TmsSetting::create(array_merge(
            ['factory_id' => $this->factory->id],
            TmsSetting::defaultValues()
        ));

        $this->driver = TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->driverEmployee->id,
            'ot_rate'            => 150,
            'is_overtime_active' => true,
            'status'             => 'active',
        ]);
    }

    public function test_settings_save_billing_fields(): void
    {
        $this->actingAs($this->authority)
            ->put(route('admin.tms.settings.update'), [
                'office_start'              => '09:00',
                'office_end'                => '17:00',
                'ot_basis'                  => 'global_office_time',
                'company_night_bill'        => 130,
                'company_holiday_duty_bill' => 330,
                'rental_ot_hourly_rate'     => 125,
                'rental_km_rate'            => 15,
                'weekend_days'              => [5, 6],
            ])
            ->assertRedirect(route('admin.tms.settings.index'));

        $settings = TmsSetting::current();
        $this->assertSame('130.00', $settings->company_night_bill);
        $this->assertSame('15.00', $settings->rental_km_rate);
        $this->assertSame([5, 6], $settings->weekend_days);
    }

    public function test_rate_resolver_uses_vehicle_then_vendor_then_factory(): void
    {
        TmsSetting::current()->update(['rental_km_rate' => 12]);

        $vendor = TmsRentalVendor::create([
            'factory_id'     => $this->factory->id,
            'name'           => 'ABC Rent',
            'rental_km_rate' => 14,
            'status'         => 'active',
        ]);

        $vehicleWithVendorRate = TmsVehicle::create([
            'factory_id'       => $this->factory->id,
            'name'             => 'Rent A',
            'reg_number'       => 'RNT-001',
            'type'             => 'rental',
            'rental_vendor_id' => $vendor->id,
            'status'           => 'available',
        ]);

        $vehicleWithOverride = TmsVehicle::create([
            'factory_id'       => $this->factory->id,
            'name'             => 'Rent B',
            'reg_number'       => 'RNT-002',
            'type'             => 'rental',
            'rental_vendor_id' => $vendor->id,
            'rental_km_rate'   => 18,
            'status'           => 'available',
        ]);

        $resolver = app(RentalRateResolver::class);

        $this->assertSame(14.0, $resolver->resolve($vehicleWithVendorRate));
        $this->assertSame(18.0, $resolver->resolve($vehicleWithOverride));

        $vendor->update(['rental_km_rate' => null]);
        $vehicleWithVendorRate->refresh();
        $this->assertSame(12.0, $resolver->resolve($vehicleWithVendorRate));
    }

    public function test_rental_trip_calculates_km_charge_at_12_per_km(): void
    {
        $vendor = TmsRentalVendor::create([
            'factory_id' => $this->factory->id,
            'name'       => 'City Rent',
            'status'     => 'active',
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Rent Hiace',
            'reg_number'         => 'RNT-100',
            'type'               => 'rental',
            'rental_vendor_id'   => $vendor->id,
            'passenger_capacity' => 8,
            'status'             => 'available',
            'last_odometer_km'   => 1000,
        ]);

        $this->driver->syncAssignedVehicles([$vehicle->id], $vehicle->id);

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.odometer.morning'), [
                'vehicle_id' => $vehicle->id,
                'morning_km' => 1000,
            ])
            ->assertRedirect(route('employee.transport.odometer', ['vehicle_id' => $vehicle->id]));

        $log = \App\Models\Tms\TmsDailyOdometerLog::first();

        Carbon::setTestNow('2026-06-17 18:00:00');

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.odometer.evening', $log), ['evening_km' => 1045])
            ->assertRedirect(route('employee.transport.odometer', ['vehicle_id' => $vehicle->id]));

        $vehicle->refresh();

        $this->assertSame(1045.0, (float) $vehicle->last_odometer_km);

        $charge = TmsRentalVehicleCharge::where('odometer_log_id', $log->id)->first();
        $this->assertNotNull($charge);
        $this->assertSame(45.0, (float) $charge->total_km);
        $this->assertSame(12.0, (float) $charge->km_rate);
        $this->assertSame(540.0, (float) $charge->amount);
        $this->assertSame('pending', $charge->payment_status);
    }

    public function test_rental_trip_no_longer_requires_trip_km(): void
    {
        $vendor = TmsRentalVendor::create([
            'factory_id' => $this->factory->id,
            'name'       => 'City Rent',
            'status'     => 'active',
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'       => $this->factory->id,
            'name'             => 'Rent Car',
            'reg_number'       => 'RNT-200',
            'type'             => 'rental',
            'rental_vendor_id' => $vendor->id,
            'status'           => 'available',
        ]);

        $this->driver->update(['default_vehicle_id' => $vehicle->id]);

        $requester = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'TMS-R002',
            'name'          => 'Requester Two',
            'status'        => 'active',
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-17 10:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $this->driver->id,
                'vehicle_id'  => $vehicle->id,
            ]);

        $trip = TmsTripLog::first();

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.trips.start', $trip), [])
            ->assertRedirect(route('employee.transport.trips'));

        Carbon::setTestNow('2026-06-17 21:00:00');

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.trips.end', $trip), [])
            ->assertRedirect(route('employee.transport.trips'));

        $trip->refresh();
        $this->assertNull($trip->start_km);
        $this->assertNull($trip->end_km);
        $this->assertSame(0.0, (float) $trip->rental_charge_amount);
        $this->assertNull(TmsRentalVehicleCharge::where('trip_log_id', $trip->id)->first());
    }

    public function test_own_vehicle_trip_works_without_km(): void
    {
        $vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Own Car',
            'reg_number'         => 'OWN-100',
            'type'               => 'own',
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);

        $this->driver->update(['default_vehicle_id' => $vehicle->id]);

        $requester = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'TMS-R003',
            'name'          => 'Requester Three',
            'status'        => 'active',
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'Office',
            'pickup_at'          => '2026-06-17 10:00',
            'purpose'            => 'Meeting',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $this->driver->id,
                'vehicle_id'  => $vehicle->id,
            ]);

        $trip = TmsTripLog::first();

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.trips.start', $trip))
            ->assertRedirect(route('employee.transport.trips'));

        Carbon::setTestNow('2026-06-17 21:00:00');

        $this->actingAs($this->driverPortal, 'employee')
            ->post(route('employee.transport.trips.end', $trip))
            ->assertRedirect(route('employee.transport.trips'));

        $trip->refresh();
        $this->assertNull($trip->start_km);
        $this->assertNull($trip->end_km);
        $this->assertSame(0.0, (float) $trip->rental_charge_amount);
        $this->assertNull(TmsRentalVehicleCharge::where('trip_log_id', $trip->id)->first());
    }

    public function test_daily_billing_skips_incomplete_odometer_log(): void
    {
        $vendor = TmsRentalVendor::create([
            'factory_id' => $this->factory->id,
            'name'       => 'Vendor',
            'status'     => 'active',
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'       => $this->factory->id,
            'name'             => 'Rent',
            'reg_number'       => 'RNT-300',
            'type'             => 'rental',
            'rental_vendor_id' => $vendor->id,
            'status'           => 'available',
        ]);

        $log = \App\Models\Tms\TmsDailyOdometerLog::create([
            'factory_id'  => $this->factory->id,
            'vehicle_id'  => $vehicle->id,
            'log_date'    => '2026-06-17',
            'morning_km'  => 1000,
            'evening_km'  => null,
        ]);

        $charge = app(\App\Services\Tms\DailyRentalBillingService::class)->syncFromOdometerLog($log);
        $this->assertNull($charge);
    }

    public function test_rental_charges_hub_and_unmark_paid(): void
    {
        $vendor = TmsRentalVendor::create([
            'factory_id' => $this->factory->id,
            'name'       => 'ABC Rent',
            'status'     => 'active',
        ]);

        $charge = TmsRentalVehicleCharge::create([
            'factory_id'       => $this->factory->id,
            'vehicle_id'       => TmsVehicle::create([
                'factory_id'       => $this->factory->id,
                'name'             => 'Rent Van',
                'reg_number'       => 'RNT-400',
                'type'             => 'rental',
                'rental_vendor_id' => $vendor->id,
                'status'           => 'available',
            ])->id,
            'rental_vendor_id' => $vendor->id,
            'log_date'         => '2026-06-17',
            'total_km'         => 50,
            'km_rate'          => 10,
            'amount'           => 500,
            'payment_status'   => 'pending',
        ]);

        $this->actingAs($this->authority)
            ->get(route('admin.tms.rental-charges.index'))
            ->assertOk()
            ->assertSee('Rent Van');

        $this->actingAs($this->authority)
            ->post(route('admin.tms.rental-charges.mark-paid', $charge))
            ->assertRedirect();

        $this->assertSame('paid', $charge->fresh()->payment_status);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.rental-charges.unmark-paid', $charge))
            ->assertRedirect();

        $charge->refresh();
        $this->assertSame('pending', $charge->payment_status);
        $this->assertNull($charge->paid_at);
    }
}
