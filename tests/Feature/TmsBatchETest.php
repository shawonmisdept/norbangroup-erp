<?php

namespace Tests\Feature;

use App\Contracts\SmsGateway;
use App\Models\AppSetting;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\Tms\TmsDailyOdometerLog;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsMaintenancePartCatalog;
use App\Models\Tms\TmsRentalVendor;
use App\Models\Tms\TmsRentalVehicleCharge;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use App\Services\Tms\DailyRentalBillingService;
use App\Services\Tms\TmsNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsBatchETest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $user;

    private Department $dept;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');

        $this->factory = Factory::create(['name' => 'Batch E Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Batch E',
            'permissions' => [
                'tms.reports.view',
                'tms.maintenance.view',
                'tms.maintenance.manage',
                'tms.requests.approve',
                'tms.overtime.manage',
            ],
        ]);

        $this->user = User::create([
            'name'       => 'Batch E Admin',
            'email'      => 'batche@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);

        TmsSetting::create(array_merge(
            ['factory_id' => $this->factory->id],
            TmsSetting::defaultValues()
        ));

        $shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Day',
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $this->dept = Department::create(['factory_id' => $this->factory->id, 'name' => 'Production', 'is_active' => true]);

        $this->employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'department_id' => $this->dept->id,
            'employee_code' => 'BE-E001',
            'name'          => 'Requester',
            'phone'         => '01700000001',
            'status'        => 'active',
        ]);
    }

    public function test_department_chargeback_report_shows_driver_pay(): void
    {
        $vehicle = $this->createVehicle('own');
        $driver = $this->createDriver($vehicle);

        $request = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->employee->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-15 10:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 2,
            'status'             => 'pending',
        ]);

        $trip = $this->createCompletedTrip($vehicle, $driver, 500, $request);
        $request->update(['status' => 'completed', 'trip_log_id' => $trip->id]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.reports.index', ['tab' => 'department_chargeback']))
            ->assertOk()
            ->assertSee('Production')
            ->assertSee('500.00');
    }

    public function test_payroll_ot_export_csv(): void
    {
        $vehicle = $this->createVehicle('own');
        $driver = $this->createDriver($vehicle);

        $request = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->employee->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-15 10:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $trip = $this->createCompletedTrip($vehicle, $driver, 320, $request);

        TmsDriverOvertimePayment::create([
            'trip_log_id'    => $trip->id,
            'driver_id'      => $driver->id,
            'amount'         => 320,
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.tms.reports.export', [
                'report' => 'payroll_ot',
                'from'   => '2026-06-01',
                'to'     => '2026-06-30',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('Employee Code', $response->streamedContent());
        $this->assertStringContainsString('BE-DRV', $response->streamedContent());
    }

    public function test_maintenance_parts_catalog_crud(): void
    {
        $this->actingAs($this->user)
            ->post(route('admin.tms.maintenance.parts.store'), [
                'factory_id'         => $this->factory->id,
                'name'               => 'Engine Oil',
                'unit'               => 'Ltr',
                'default_unit_price' => 850,
                'is_active'          => 1,
            ])
            ->assertRedirect(route('admin.tms.maintenance.parts.index'));

        $part = TmsMaintenancePartCatalog::first();
        $this->assertNotNull($part);
        $this->assertSame('Engine Oil', $part->name);

        $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.parts.index'))
            ->assertOk()
            ->assertSee('Engine Oil');
    }

    public function test_rental_billing_catch_up_creates_missing_charge(): void
    {
        $vendor = TmsRentalVendor::create([
            'factory_id' => $this->factory->id,
            'name'       => 'Vendor',
            'status'     => 'active',
        ]);

        $vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Rental',
            'reg_number'         => 'R-1',
            'type'               => 'rental',
            'rental_vendor_id'   => $vendor->id,
            'passenger_capacity' => 4,
            'status'             => 'available',
            'rental_km_rate'     => 50,
        ]);

        $log = TmsDailyOdometerLog::create([
            'factory_id'  => $this->factory->id,
            'vehicle_id'  => $vehicle->id,
            'log_date'    => '2026-06-15',
            'morning_km'  => 1000,
            'evening_km'  => 1020,
        ]);

        $this->assertNull(TmsRentalVehicleCharge::where('odometer_log_id', $log->id)->first());

        $result = app(DailyRentalBillingService::class)->catchUpMissingCharges('2026-06-15', '2026-06-15');

        $this->assertSame(1, $result['created']);
        $charge = TmsRentalVehicleCharge::where('odometer_log_id', $log->id)->first();
        $this->assertNotNull($charge);
        $this->assertSame(1000.0, (float) $charge->amount);
    }

    public function test_tms_sms_sent_when_enabled_on_approval(): void
    {
        $sms = $this->createMock(SmsGateway::class);
        $sms->expects($this->atLeastOnce())->method('send')->willReturn(true);
        $this->app->instance(SmsGateway::class, $sms);

        AppSetting::current()->update([
            'notify_sms_tms' => true,
            'sms_provider'   => 'log',
        ]);
        AppSetting::clearCache();

        $vehicle = $this->createVehicle('own');
        $driver = $this->createDriver($vehicle);

        $request = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->employee->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-17 10:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'approved',
            'driver_id'          => $driver->id,
            'vehicle_id'         => $vehicle->id,
        ]);

        app(TmsNotificationService::class)->requestApproved($request->load(['employee', 'driver.employee']));
    }

    public function test_tms_popup_respects_disabled_master_toggle(): void
    {
        AppSetting::current()->update(['notify_popup_tms' => false]);
        AppSetting::clearCache();

        $vehicle = $this->createVehicle('own');
        $driver = $this->createDriver($vehicle);

        $request = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $this->employee->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-17 10:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);

        $before = $this->user->notifications()->count();
        app(TmsNotificationService::class)->requestSubmitted($request);
        $this->assertSame($before, $this->user->fresh()->notifications()->count());
    }

    private function createVehicle(string $type): TmsVehicle
    {
        return TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Car',
            'reg_number'         => 'BE-1',
            'type'               => $type,
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);
    }

    private function createDriver(TmsVehicle $vehicle): TmsDriver
    {
        $driverEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'BE-DRV',
            'name'          => 'Driver',
            'phone'         => '01700000002',
            'status'        => 'active',
        ]);

        return TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $driverEmployee->id,
            'default_vehicle_id' => $vehicle->id,
            'ot_rate'            => 150,
            'is_overtime_active' => true,
            'status'             => 'active',
        ]);
    }

    private function createCompletedTrip(TmsVehicle $vehicle, TmsDriver $driver, float $pay, TmsTransportRequest $request): TmsTripLog
    {
        return TmsTripLog::create([
            'transport_request_id' => $request->id,
            'factory_id'           => $this->factory->id,
            'vehicle_id'           => $vehicle->id,
            'driver_id'            => $driver->id,
            'driver_type'          => 'company',
            'total_passengers'     => 1,
            'duty_start_at'        => '2026-06-15 10:00',
            'duty_end_at'          => '2026-06-15 18:00',
            'total_driver_pay'     => $pay,
            'trip_status'          => 'completed',
        ]);
    }
}
