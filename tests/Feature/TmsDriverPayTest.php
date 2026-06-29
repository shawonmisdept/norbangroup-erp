<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Holiday;
use App\Models\Role;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsRentalVendor;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use App\Services\Tms\RentalDriverPortalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsDriverPayTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $authority;

    private TmsDriver $companyDriver;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');

        $this->factory = Factory::create(['name' => 'Pay Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Admin',
            'permissions' => ['tms.requests.approve', 'tms.settings.manage', 'tms.trips.view'],
        ]);

        $this->authority = User::create([
            'name'       => 'Admin',
            'email'      => 'pay-admin@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);

        TmsSetting::create(array_merge(
            ['factory_id' => $this->factory->id],
            TmsSetting::defaultValues()
        ));

        $employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'DRV-001',
            'name'          => 'Company Driver',
            'status'        => 'active',
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Own',
            'reg_number'         => 'OWN-1',
            'type'               => 'own',
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);

        $this->companyDriver = TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $employee->id,
            'default_vehicle_id' => $vehicle->id,
            'ot_rate'            => 150,
            'is_overtime_active' => true,
            'status'             => 'active',
        ]);
    }

    public function test_company_driver_weekday_gets_night_bill_not_hourly(): void
    {
        $trip = $this->completeCompanyTrip('2026-06-17 10:00', '2026-06-17 21:00:00');

        $this->assertSame('night_bill', $trip->bill_type);
        $this->assertSame(120.0, (float) $trip->night_bill_amount);
        $this->assertSame(0.0, (float) $trip->ot_hours);
        $this->assertSame(120.0, (float) $trip->total_driver_pay);
    }

    public function test_company_driver_holiday_gets_holiday_bill_plus_hourly_ot(): void
    {
        Holiday::create([
            'factory_id' => $this->factory->id,
            'name'       => 'Eid',
            'date'       => '2026-06-20',
            'is_active'  => true,
        ]);

        $trip = $this->completeCompanyTrip('2026-06-20 10:00', '2026-06-20 21:00:00');

        $this->assertSame('holiday_mixed', $trip->bill_type);
        $this->assertSame(320.0, (float) $trip->holiday_duty_amount);
        $this->assertSame(4.0, (float) $trip->ot_hours);
        $this->assertSame(600.0, (float) $trip->ot_hourly_amount);
        $this->assertSame(920.0, (float) $trip->total_driver_pay);
    }

    public function test_rental_driver_gets_hourly_ot_from_settings(): void
    {
        $vendor = TmsRentalVendor::create([
            'factory_id' => $this->factory->id,
            'name'       => 'Vendor',
            'status'     => 'active',
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Rent',
            'reg_number'         => 'R-1',
            'type'               => 'rental',
            'rental_vendor_id'   => $vendor->id,
            'passenger_capacity' => 4,
            'status'             => 'available',
            'last_odometer_km'   => 0,
        ]);

        $rentalDriver = TmsRentalDriver::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Rental Ali',
            'mobile'             => '01700000000',
            'default_vehicle_id' => $vehicle->id,
            'status'             => 'active',
        ]);

        $portal = RentalDriverPortalService::resetPassword($rentalDriver, 'password');

        $requester = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'REQ-1',
            'name'          => 'Requester',
            'status'        => 'active',
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-17 10:00',
            'purpose'            => 'Trip',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type'      => 'rental',
                'rental_driver_id' => $rentalDriver->id,
                'vehicle_id'       => $vehicle->id,
            ]);

        $trip = TmsTripLog::first();

        $this->actingAs($portal, 'rental_driver')
            ->post(route('rental.trips.start', $trip));

        Carbon::setTestNow('2026-06-17 21:00:00');

        $this->actingAs($portal, 'rental_driver')
            ->post(route('rental.trips.end', $trip));

        $trip->refresh();

        $this->assertSame('rental', $trip->driver_type);
        $this->assertSame('hourly', $trip->bill_type);
        $this->assertSame(0.0, (float) $trip->night_bill_amount);
        $this->assertSame(4.0, (float) $trip->ot_hours);
        $this->assertSame(480.0, (float) $trip->ot_hourly_amount);
        $this->assertSame(480.0, (float) $trip->total_driver_pay);
    }

    private function completeCompanyTrip(string $pickupAt, string $endAt): TmsTripLog
    {
        $requester = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'REQ-' . uniqid(),
            'name'          => 'Requester',
            'status'        => 'active',
        ]);

        $transportRequest = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => $pickupAt,
            'purpose'            => 'Trip',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.requests.approve', $transportRequest), [
                'driver_type' => 'company',
                'driver_id'   => $this->companyDriver->id,
            ]);

        $trip = TmsTripLog::first();

        $this->actingAs($this->authority)
            ->post(route('admin.tms.trips.start', $trip));

        Carbon::setTestNow($endAt);

        $this->actingAs($this->authority)
            ->post(route('admin.tms.trips.end', $trip));

        return $trip->fresh();
    }
}
