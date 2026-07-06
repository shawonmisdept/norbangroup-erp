<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Role;
use App\Models\Tms\TmsDailyOdometerLog;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsRentalDriverPortalUser;
use App\Models\Tms\TmsRentalVendor;
use App\Models\Tms\TmsRentalVehicleCharge;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use App\Services\Tms\RentalDriverPortalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalDriverPortalTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $authority;

    private TmsRentalDriver $rentalDriver;

    private TmsRentalDriverPortalUser $portalUser;

    private TmsVehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-24 08:00:00');

        $this->factory = Factory::create(['name' => 'TMS Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Authority',
            'permissions' => ['tms.requests.approve', 'tms.rental_drivers.manage', 'tms.rental_drivers.view'],
        ]);

        $this->authority = User::create([
            'name'       => 'Transport Admin',
            'email'      => 'tms-rental-portal@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);

        TmsSetting::create(array_merge(
            ['factory_id' => $this->factory->id],
            TmsSetting::defaultValues()
        ));

        $vendor = TmsRentalVendor::create([
            'factory_id' => $this->factory->id,
            'name'       => 'City Rent',
            'status'     => 'active',
        ]);

        $this->vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Rent Hiace',
            'reg_number'         => 'RNT-PORTAL',
            'type'               => 'rental',
            'rental_vendor_id'   => $vendor->id,
            'passenger_capacity' => 8,
            'status'             => 'available',
            'last_odometer_km'   => 5000,
        ]);

        $this->rentalDriver = TmsRentalDriver::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Rental Nazrul',
            'mobile'             => '01710000001',
            'default_vehicle_id' => $this->vehicle->id,
            'status'             => 'active',
        ]);

        RentalDriverPortalService::resetPassword($this->rentalDriver, 'password');
        $this->portalUser = TmsRentalDriverPortalUser::where('rental_driver_id', $this->rentalDriver->id)->firstOrFail();
    }

    public function test_rental_root_redirects_guest_to_login(): void
    {
        $this->get('/rental')
            ->assertRedirect(route('rental.login'));
    }

    public function test_rental_root_redirects_authenticated_driver_to_dashboard(): void
    {
        $this->actingAs($this->portalUser, 'rental_driver')
            ->get('/rental')
            ->assertRedirect(route('rental.dashboard'));
    }

    public function test_rental_driver_can_login_and_view_dashboard(): void
    {
        $this->post(route('rental.login.store'), [
            'mobile'   => '01710000001',
            'password' => 'password',
        ])->assertRedirect(route('rental.dashboard'));

        $this->actingAs($this->portalUser, 'rental_driver')
            ->get(route('rental.dashboard'))
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee('Quick Actions');
    }

    public function test_rental_driver_starts_and_ends_trip_without_km(): void
    {
        $trip = $this->createAssignedTrip();

        $this->actingAs($this->portalUser, 'rental_driver')
            ->post(route('rental.trips.start', $trip))
            ->assertRedirect(route('rental.trips'));

        $trip->refresh();
        $this->assertSame('in_progress', $trip->trip_status);
        $this->assertNull($trip->start_km);

        Carbon::setTestNow('2026-06-24 18:00:00');

        $this->actingAs($this->portalUser, 'rental_driver')
            ->post(route('rental.trips.end', $trip))
            ->assertRedirect(route('rental.trips'));

        $trip->refresh();
        $this->assertSame('completed', $trip->trip_status);
        $this->assertNull($trip->end_km);
        $this->assertSame(0.0, (float) $trip->rental_charge_amount);

        $this->actingAs($this->portalUser, 'rental_driver')
            ->get(route('rental.trips'))
            ->assertOk()
            ->assertSee('Recent Completed')
            ->assertSee('Requester')
            ->assertDontSee('Start Trip');
    }

    public function test_rental_driver_daily_odometer_creates_billing_charge(): void
    {
        $this->actingAs($this->portalUser, 'rental_driver')
            ->get(route('rental.odometer.morning.create'))
            ->assertOk()
            ->assertSee('Record Morning KM');

        $this->actingAs($this->portalUser, 'rental_driver')
            ->post(route('rental.odometer.morning.store'), [
                'log_date'   => '2026-06-24',
                'morning_km' => 5000,
            ])
            ->assertRedirect(route('rental.odometer'));

        $log = TmsDailyOdometerLog::first();
        $this->assertNotNull($log);
        $this->assertSame(5000.0, (float) $log->morning_km);

        Carbon::setTestNow('2026-06-24 20:00:00');

        $this->actingAs($this->portalUser, 'rental_driver')
            ->get(route('rental.odometer.evening.create', $log))
            ->assertOk()
            ->assertSee('Record Evening KM');

        $this->actingAs($this->portalUser, 'rental_driver')
            ->post(route('rental.odometer.evening.store', $log), ['evening_km' => 5045])
            ->assertRedirect(route('rental.odometer'));

        $log->refresh();
        $this->assertSame(45.0, $log->dailyKm());

        $charge = TmsRentalVehicleCharge::where('odometer_log_id', $log->id)->first();
        $this->assertNotNull($charge);
        $this->assertSame(45.0, (float) $charge->total_km);
        $this->assertSame(540.0, (float) $charge->amount);
        $this->assertSame('pending', $charge->payment_status);
        $this->assertNull($charge->trip_log_id);
    }

    public function test_trip_assignment_notifies_rental_driver_portal(): void
    {
        $this->createAssignedTrip();

        $this->assertTrue(
            $this->portalUser->fresh()->notifications()->where('data->type', 'tms_trip_assigned')->exists()
        );

        $this->actingAs($this->portalUser, 'rental_driver')
            ->get(route('rental.trips'))
            ->assertOk()
            ->assertSee('Start Trip');
    }

    private function createAssignedTrip(): TmsTripLog
    {
        $requester = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'REQ-R001',
            'name'          => 'Requester',
            'status'        => 'active',
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'Airport',
            'pickup_at'          => '2026-06-24 10:00',
            'purpose'            => 'Pickup',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type'      => 'rental',
                'rental_driver_id' => $this->rentalDriver->id,
                'vehicle_id'       => $this->vehicle->id,
            ]);

        return TmsTripLog::firstOrFail();
    }
}
