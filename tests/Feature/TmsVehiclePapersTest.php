<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsVehicle;
use App\Models\Tms\TmsVehiclePaperRenewal;
use App\Models\User;
use App\Services\Tms\VehiclePaperService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsVehiclePapersTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $officer;

    private TmsVehicle $vehicle;

    private TmsDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');

        $this->factory = Factory::create(['name' => 'HO', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Transport Officer',
            'permissions' => [
                'tms.dashboard.view',
                'tms.vehicles.view',
                'tms.vehicles.manage',
                'tms.requests.view',
                'tms.requests.approve',
                'tms.drivers.view',
                'tms.trips.view',
            ],
        ]);

        $this->officer = User::create([
            'name'       => 'Transport Officer',
            'email'      => 'transport@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);

        TmsSetting::create([
            'factory_id'   => $this->factory->id,
            'office_start' => '09:00:00',
            'office_end'   => '17:00:00',
            'ot_basis'     => 'global_office_time',
        ]);

        $shift = Shift::create([
            'factory_id'    => $this->factory->id,
            'name'          => 'Day',
            'start_time'    => '09:00:00',
            'end_time'      => '17:00:00',
            'break_minutes' => 60,
            'is_active'     => true,
        ]);

        $driverEmployee = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'DRV-001',
            'name'          => 'Driver One',
            'phone'         => '01700000001',
            'status'        => 'active',
        ]);

        $requester = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'EMP-001',
            'name'          => 'Requester',
            'status'        => 'active',
        ]);

        $this->vehicle = TmsVehicle::create([
            'factory_id'           => $this->factory->id,
            'name'                 => 'Toyota Axio',
            'vehicle_category'     => 'sedan',
            'model_year'           => 2022,
            'engine_cc'            => 1500,
            'reg_number'           => 'DM-GHA-22-1042',
            'type'                 => 'own',
            'fuel_type'            => 'octane',
            'passenger_capacity'   => 4,
            'status'               => 'available',
            'fitness_expires_at'   => '2028-01-15',
            'tax_token_expires_at' => '2026-06-01',
            'insurance_expires_at' => '2026-07-15',
        ]);

        $this->driver = TmsDriver::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $driverEmployee->id,
            'default_vehicle_id' => $this->vehicle->id,
            'status'             => 'active',
        ]);

        $this->vehicle->update(['primary_driver_id' => $this->driver->id]);

        TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $requester->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'Airport',
            'pickup_at'          => '2026-06-17 14:00:00',
            'purpose'            => 'Visit',
            'passenger_count'    => 1,
            'status'             => 'pending',
        ]);
    }

    public function test_papers_status_page_lists_vehicle_with_expired_tax_token(): void
    {
        $this->actingAs($this->officer)
            ->get(route('admin.tms.vehicles.papers'))
            ->assertOk()
            ->assertSee('Toyota Axio')
            ->assertSee('DM-GHA-22-1042')
            ->assertSee('Driver One');
    }

    public function test_papers_status_page_supports_search_and_pagination(): void
    {
        TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Hidden Van',
            'reg_number'         => 'DM-ZZ-99-9999',
            'type'               => 'own',
            'fuel_type'          => 'diesel',
            'passenger_capacity' => 2,
            'status'             => 'available',
        ]);

        $this->actingAs($this->officer)
            ->get(route('admin.tms.vehicles.papers', ['search' => 'DM-GHA-22-1042']))
            ->assertOk()
            ->assertSee('Toyota Axio')
            ->assertDontSee('Hidden Van');

        $this->actingAs($this->officer)
            ->get(route('admin.tms.vehicles.papers', ['per_page' => 50]))
            ->assertOk()
            ->assertSee('Show per page')
            ->assertSee('Showing');
    }

    public function test_papers_status_ajax_returns_results_partial(): void
    {
        $this->actingAs($this->officer)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.tms.vehicles.papers', ['search' => 'Toyota']))
            ->assertOk()
            ->assertSee('Toyota Axio')
            ->assertDontSee('x-data="vehiclePapersIndex"');
    }

    public function test_papers_status_print_matches_excel_layout(): void
    {
        $this->actingAs($this->officer)
            ->get(route('admin.tms.vehicles.papers.print'))
            ->assertOk()
            ->assertSee('Norban Group')
            ->assertSee('Vehicle Papers Status')
            ->assertSee('Toyota Axio')
            ->assertSee('DM-GHA-22-1042')
            ->assertSee('Date of')
            ->assertSee('Taxtoken')
            ->assertSee('Driver Contact No');
    }

    public function test_paper_renewal_updates_vehicle_expiry_and_logs_history(): void
    {
        $this->actingAs($this->officer)
            ->post(route('admin.tms.vehicles.paper-renewals.store', $this->vehicle), [
                'paper_type'     => 'tax_token',
                'new_expires_at' => '2027-06-01',
                'cost'           => 3500,
                'receipt_number' => 'TX-100',
                'notes'          => 'Annual renewal',
            ])
            ->assertRedirect(route('admin.tms.vehicles.show', $this->vehicle));

        $this->vehicle->refresh();

        $this->assertSame('2027-06-01', $this->vehicle->tax_token_expires_at->toDateString());

        $renewal = TmsVehiclePaperRenewal::first();
        $this->assertNotNull($renewal);
        $this->assertSame('tax_token', $renewal->paper_type);
        $this->assertSame('2026-06-01', $renewal->previous_expires_at->toDateString());
        $this->assertSame('2027-06-01', $renewal->new_expires_at->toDateString());
    }

    public function test_expired_paper_does_not_block_trip_approval(): void
    {
        $request = TmsTransportRequest::first();

        $this->actingAs($this->officer)
            ->post(route('admin.tms.requests.approve', $request), [
                'driver_type' => 'company',
                'driver_id'   => $this->driver->id,
                'vehicle_id'  => $this->vehicle->id,
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertNotNull($request->trip_log_id);
    }

    public function test_vehicle_paper_service_marks_expired_and_urgent_statuses(): void
    {
        $service = app(VehiclePaperService::class);

        $this->assertSame(
            VehiclePaperService::STATUS_EXPIRED,
            $service->statusForDate(Carbon::parse('2026-06-01'))
        );

        $this->assertSame(
            VehiclePaperService::STATUS_URGENT,
            $service->statusForDate(Carbon::parse('2026-07-01'))
        );

        $warnings = $service->warningMessagesForVehicle($this->vehicle);
        $this->assertNotEmpty($warnings);
        $this->assertTrue(
            collect($warnings)->contains(fn ($w) => str_contains($w, 'Tax Token'))
        );
    }

    public function test_alert_papers_lists_non_ok_documents(): void
    {
        $service = app(VehiclePaperService::class);

        $alerts = $service->alertPapersForVehicle($this->vehicle);

        $this->assertNotEmpty($alerts);
        $this->assertTrue(
            collect($alerts)->contains(fn (array $paper) => $paper['label'] === 'Tax Token' && $paper['status'] === VehiclePaperService::STATUS_EXPIRED)
        );
        $this->assertTrue(
            collect($alerts)->contains(fn (array $paper) => $paper['label'] === 'Insurance' && $paper['status'] === VehiclePaperService::STATUS_URGENT)
        );
    }

    public function test_dashboard_shows_paper_alert_counts(): void
    {
        $this->actingAs($this->officer)
            ->get(route('admin.tms.dashboard'))
            ->assertOk()
            ->assertSee('Papers Expired')
            ->assertSee('Add Vehicle')
            ->assertSee(route('admin.tms.vehicles.create'), false);
    }

    public function test_vehicle_form_accepts_extended_asset_and_paper_fields(): void
    {
        $this->actingAs($this->officer)
            ->put(route('admin.tms.vehicles.update', $this->vehicle), [
                'factory_id'                => $this->factory->id,
                'name'                      => 'Toyota Axio',
                'vehicle_category'          => 'sedan',
                'model_year'                => 2022,
                'engine_cc'                 => 1500,
                'reg_number'                => 'DM-GHA-22-1042',
                'type'                      => 'own',
                'fuel_type'                 => 'octane',
                'passenger_capacity'        => 4,
                'status'                    => 'available',
                'purchase_date'             => '2022-03-15',
                'registration_date'       => '2022-04-20',
                'purchase_value'            => 2884700,
                'is_dedicated'              => 1,
                'fitness_expires_at'        => '2028-01-15',
                'tax_token_expires_at'      => '2026-06-01',
                'insurance_expires_at'      => '2026-07-15',
                'registration_paper_status' => 'ok',
                'allocated_employee_id'     => '',
                'primary_driver_id'         => $this->driver->id,
            ])
            ->assertRedirect(route('admin.tms.vehicles.show', $this->vehicle));

        $this->vehicle->refresh();
        $this->assertSame('2884700.00', $this->vehicle->purchase_value);
        $this->assertTrue($this->vehicle->is_dedicated);
    }
}
