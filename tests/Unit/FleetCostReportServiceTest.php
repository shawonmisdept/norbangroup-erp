<?php

namespace Tests\Unit;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Role;
use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsRentalVehicleCharge;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use App\Services\Tms\FleetCostReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FleetCostReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private FleetCostReportService $service;

    private Factory $factory;

    private User $user;

    private TmsVehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FleetCostReportService::class);
        $this->factory = Factory::create(['name' => 'Cost Summary Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Cost Summary Admin',
            'permissions' => ['tms.reports.view'],
        ]);

        $this->user = User::create([
            'name'       => 'Cost User',
            'email'      => 'cost-summary@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);

        $this->vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Cost Van',
            'reg_number'         => 'DM-COST-01',
            'type'               => 'own',
            'fuel_type'          => 'diesel',
            'passenger_capacity' => 8,
            'status'             => 'available',
        ]);
    }

    public function test_summarize_maintenance_respects_date_vehicle_and_workshop(): void
    {
        TmsMaintenanceBill::create([
            'factory_id'    => $this->factory->id,
            'vehicle_id'    => $this->vehicle->id,
            'bill_no'       => 'M-1',
            'bill_date'     => '2026-06-10',
            'workshop_name' => 'Jayma Motors',
            'total_amount'  => 1500,
            'paid_by'       => 'company',
        ]);

        TmsMaintenanceBill::create([
            'factory_id'    => $this->factory->id,
            'vehicle_id'    => $this->vehicle->id,
            'bill_no'       => 'M-2',
            'bill_date'     => '2026-06-15',
            'workshop_name' => 'JK Motors',
            'total_amount'  => 2500,
            'paid_by'       => 'rental_party',
        ]);

        $request = Request::create('/');
        $request->setUserResolver(fn () => $this->user);

        $all = $this->service->summarizeMaintenance($request, [
            'from' => '2026-06-01',
            'to'   => '2026-06-30',
        ]);

        $this->assertSame(2, $all['bill_count']);
        $this->assertEqualsWithDelta(4000, $all['total'], 0.01);
        $this->assertEqualsWithDelta(1500, $all['company'], 0.01);
        $this->assertEqualsWithDelta(2500, $all['rental_party'], 0.01);

        $filtered = $this->service->summarizeMaintenance($request, [
            'from'     => '2026-06-01',
            'to'       => '2026-06-30',
            'workshop' => 'Jayma Motors',
        ]);

        $this->assertSame(1, $filtered['bill_count']);
        $this->assertEqualsWithDelta(1500, $filtered['total'], 0.01);
        $this->assertEqualsWithDelta(0, $filtered['rental_party'], 0.01);
    }

    public function test_summarize_rental_charges_respects_payment_status(): void
    {
        TmsRentalVehicleCharge::create([
            'factory_id'     => $this->factory->id,
            'vehicle_id'     => $this->vehicle->id,
            'log_date'       => '2026-06-10',
            'total_km'       => 100,
            'km_rate'        => 20,
            'amount'         => 2000,
            'payment_status' => 'paid',
        ]);

        TmsRentalVehicleCharge::create([
            'factory_id'     => $this->factory->id,
            'vehicle_id'     => $this->vehicle->id,
            'log_date'       => '2026-06-12',
            'total_km'       => 50,
            'km_rate'        => 20,
            'amount'         => 1000,
            'payment_status' => 'pending',
        ]);

        $request = Request::create('/');
        $request->setUserResolver(fn () => $this->user);

        $all = $this->service->summarizeRentalCharges($request, [
            'from' => '2026-06-01',
            'to'   => '2026-06-30',
        ]);

        $this->assertSame(2, $all['entry_count']);
        $this->assertEqualsWithDelta(3000, $all['total'], 0.01);
        $this->assertEqualsWithDelta(2000, $all['paid'], 0.01);
        $this->assertEqualsWithDelta(1000, $all['pending'], 0.01);

        $pendingOnly = $this->service->summarizeRentalCharges($request, [
            'from'           => '2026-06-01',
            'to'             => '2026-06-30',
            'payment_status' => 'pending',
        ]);

        $this->assertSame(1, $pendingOnly['entry_count']);
        $this->assertEqualsWithDelta(1000, $pendingOnly['total'], 0.01);
        $this->assertEqualsWithDelta(0, $pendingOnly['paid'], 0.01);
    }

    public function test_summarize_driver_pay_respects_date_filter(): void
    {
        $employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'COST-E1',
            'name'          => 'Rider',
            'status'        => 'active',
        ]);

        $requestIn = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $employee->id,
            'pickup_location'    => 'Office',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-06-10 09:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'completed',
        ]);

        $requestOut = TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $employee->id,
            'pickup_location'    => 'Office',
            'destination_custom' => 'City',
            'pickup_at'          => '2026-05-01 09:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'completed',
        ]);

        $tripInRange = TmsTripLog::create([
            'transport_request_id' => $requestIn->id,
            'factory_id'           => $this->factory->id,
            'vehicle_id'           => $this->vehicle->id,
            'duty_start_at'        => '2026-06-10 09:00:00',
            'duty_end_at'          => '2026-06-10 18:00:00',
            'trip_status'          => 'completed',
            'total_km'             => 40,
            'total_passengers'     => 1,
        ]);

        $tripOutOfRange = TmsTripLog::create([
            'transport_request_id' => $requestOut->id,
            'factory_id'           => $this->factory->id,
            'vehicle_id'           => $this->vehicle->id,
            'duty_start_at'        => '2026-05-01 09:00:00',
            'duty_end_at'          => '2026-05-01 18:00:00',
            'trip_status'          => 'completed',
            'total_km'             => 20,
            'total_passengers'     => 1,
        ]);

        TmsDriverOvertimePayment::create([
            'trip_log_id'    => $tripInRange->id,
            'amount'         => 500,
            'payment_status' => 'paid',
        ]);

        TmsDriverOvertimePayment::create([
            'trip_log_id'    => $tripOutOfRange->id,
            'amount'         => 800,
            'payment_status' => 'pending',
        ]);

        $httpRequest = Request::create('/');
        $httpRequest->setUserResolver(fn () => $this->user);

        $summary = $this->service->summarizeDriverPay($httpRequest, [
            'from' => '2026-06-01',
            'to'   => '2026-06-30',
        ]);

        $this->assertSame(1, $summary['entry_count']);
        $this->assertEqualsWithDelta(500, $summary['total'], 0.01);
        $this->assertEqualsWithDelta(500, $summary['paid'], 0.01);
        $this->assertEqualsWithDelta(0, $summary['pending'], 0.01);
    }
}
