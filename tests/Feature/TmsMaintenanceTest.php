<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsRentalVendor;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $user;

    private TmsVehicle $ownVehicle;

    private TmsVehicle $rentalVehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create(['name' => 'NCL', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Maintenance',
            'permissions' => ['tms.maintenance.view', 'tms.maintenance.manage', 'tms.reports.view'],
        ]);

        $this->user = User::create([
            'name'       => 'Maint Admin',
            'email'      => 'maint@test.com',
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

        $sumon = Employee::create([
            'factory_id'    => $this->factory->id,
            'shift_id'      => $shift->id,
            'employee_code' => 'MAINT-E001',
            'name'          => 'Sumon Sir',
            'status'        => 'active',
        ]);

        $dept = Department::create(['factory_id' => $this->factory->id, 'name' => 'AHRS', 'is_active' => true]);
        $designation = Designation::create(['name' => 'GM', 'is_active' => true]);

        $gmEmployee = Employee::create([
            'factory_id'     => $this->factory->id,
            'shift_id'       => $shift->id,
            'department_id'  => $dept->id,
            'designation_id' => $designation->id,
            'employee_code'  => 'MAINT-E002',
            'name'           => 'GM User',
            'status'         => 'active',
        ]);

        $rentalVendor = TmsRentalVendor::create([
            'factory_id' => $this->factory->id,
            'name'       => 'ABC Rent-a-Car',
            'status'     => 'active',
        ]);

        $this->ownVehicle = TmsVehicle::create([
            'factory_id'     => $this->factory->id,
            'name'           => 'Pickup',
            'reg_number'     => 'DHK-1234-5678',
            'type'           => 'own',
            'fuel_type'      => 'diesel',
            'passenger_capacity' => 4,
            'status'         => 'available',
            'allocated_employee_id' => $sumon->id,
        ]);

        $this->rentalVehicle = TmsVehicle::create([
            'factory_id'       => $this->factory->id,
            'name'             => 'Hiace',
            'reg_number'       => 'DM-GHA-11-8402',
            'type'             => 'rental',
            'fuel_type'        => 'petrol',
            'passenger_capacity' => 8,
            'status'           => 'available',
            'rental_vendor_id' => $rentalVendor->id,
            'allocated_employee_id' => $gmEmployee->id,
        ]);
    }

    public function test_vehicle_posting_car_no_labels(): void
    {
        $this->assertSame('Company Car No: 5678', $this->ownVehicle->postingCarNoLabel());
        $this->assertSame('ABC Rent-a-Car Car No: 8402', $this->rentalVehicle->postingCarNoLabel());
    }

    public function test_bulk_post_and_unpost_maintenance_bills(): void
    {
        $bill1 = $this->seedBill($this->rentalVehicle, '14811', '2026-06-10', 40400, [['Spark Plug', 1400]]);
        $bill2 = $this->seedBill($this->ownVehicle, '20001', '2026-06-05', 10350, [['Engine Oil', 10350]]);

        $this->actingAs($this->user)
            ->post(route('admin.tms.maintenance.posting.bulk-post'), [
                'bill_ids' => [$bill1->id, $bill2->id],
                'workshop' => 'JK Motors',
                'from'     => '2026-06-01',
                'to'       => '2026-06-30',
            ])
            ->assertRedirect();

        $this->assertNotNull($bill1->fresh()->posted_to_finance_at);
        $this->assertNotNull($bill2->fresh()->posted_to_finance_at);

        $this->actingAs($this->user)
            ->post(route('admin.tms.maintenance.bills.unpost', $bill1))
            ->assertRedirect();

        $this->assertNull($bill1->fresh()->posted_to_finance_at);
        $this->assertNotNull($bill2->fresh()->posted_to_finance_at);
    }

    public function test_posting_report_shows_finance_status_column(): void
    {
        $posted = $this->seedBill($this->rentalVehicle, '14811', '2026-06-10', 40400, [['Spark Plug', 1400]]);
        $posted->update(['posted_to_finance_at' => now(), 'posted_to_finance_by' => $this->user->id]);
        $this->seedBill($this->ownVehicle, '20001', '2026-06-05', 10350, [['Engine Oil', 10350]]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.posting', [
                'workshop' => 'JK Motors',
                'from'     => '2026-06-01',
                'to'       => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Posted')
            ->assertSee('Unposted');
    }

    public function test_maintenance_index_filters(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.index', ['vehicle_id' => $this->ownVehicle->id]));

        $response->assertOk()
            ->assertSee('Pickup')
            ->assertSee('DHK-1234-5678')
            ->assertSee('Unit', false)
            ->assertSee('Type', false)
            ->assertSee('Car No', false)
            ->assertSee('Bills', false)
            ->assertSee('Last Bill', false);
        $this->assertEquals(1, substr_count($response->getContent(), 'Open Register</a>'));
        $this->assertStringNotContainsString('onclick="window.location.href=', $response->getContent());

        $response = $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.index', ['posting_vehicle_id' => $this->rentalVehicle->id]));

        $response->assertOk()
            ->assertSee('Hiace')
            ->assertSee('DM-GHA-11-8402');
        $this->assertEquals(1, substr_count($response->getContent(), 'Open Register</a>'));

        $response = $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.index', ['allocated_employee_id' => $this->ownVehicle->allocated_employee_id]));

        $response->assertOk()
            ->assertSee('Pickup');
        $this->assertEquals(1, substr_count($response->getContent(), 'Open Register</a>'));

        $response = $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.index', ['search' => 'GM']));

        $response->assertOk()
            ->assertSee('Hiace');
        $this->assertEquals(1, substr_count($response->getContent(), 'Open Register</a>'));

        $response = $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.index', ['type' => 'own']));

        $response->assertOk()
            ->assertSee('Pickup');
        $this->assertEquals(1, substr_count($response->getContent(), 'Open Register</a>'));
    }

    public function test_maintenance_index_shows_bill_stats_and_unposted_badge(): void
    {
        $this->seedBill($this->ownVehicle, 'IDX-1', '2026-06-10', 1500, [['Oil', 1500]]);
        $posted = $this->seedBill($this->ownVehicle, 'IDX-2', '2026-06-15', 2500, [['Filter', 2500]]);
        $posted->update([
            'posted_to_finance_at' => now(),
            'posted_to_finance_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.index', ['vehicle_id' => $this->ownVehicle->id]))
            ->assertOk()
            ->assertSee('4,000.00')
            ->assertSee('15 Jun 2026')
            ->assertSee('1 unposted')
            ->assertSee($this->factory->name);
    }

    public function test_maintenance_bill_crud_on_vehicle_register(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.register', $this->rentalVehicle))
            ->assertOk()
            ->assertSee('Summary Of Vehicle Maintenance');

        $this->actingAs($this->user)
            ->post(route('admin.tms.maintenance.bills.store', $this->rentalVehicle), [
                'bill_no'       => '14811',
                'bill_date'     => '2026-06-10',
                'workshop_name' => 'JK Motors',
                'paid_by'       => 'company',
                'items'         => [
                    ['item_name' => 'Spark Plug', 'quantity' => 1, 'unit' => 'Set', 'amount' => 1400],
                    ['item_name' => 'Coil', 'quantity' => 4, 'unit' => 'Pcs', 'amount' => 34000],
                    ['item_name' => 'Service Charge', 'quantity' => '', 'unit' => '', 'amount' => 5000],
                ],
            ])
            ->assertRedirect(route('admin.tms.maintenance.register', $this->rentalVehicle));

        $bill = TmsMaintenanceBill::first();
        $this->assertNotNull($bill);
        $this->assertSame('14811', $bill->bill_no);
        $this->assertSame(40400.0, (float) $bill->total_amount);
        $this->assertCount(3, $bill->items);

        $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.register', $this->rentalVehicle))
            ->assertSee('14811')
            ->assertSee('Spark Plug')
            ->assertSee('40,400.00');
    }

    public function test_duplicate_bill_no_is_rejected_for_same_vehicle_and_date(): void
    {
        TmsMaintenanceBill::create([
            'factory_id'    => $this->factory->id,
            'vehicle_id'    => $this->rentalVehicle->id,
            'bill_no'       => '14811',
            'bill_date'     => '2026-06-10',
            'workshop_name' => 'JK Motors',
            'total_amount'  => 100,
            'paid_by'       => 'company',
        ]);

        $payload = [
            'bill_no'       => '14811',
            'bill_date'     => '2026-06-11',
            'workshop_name' => 'JK Motors',
            'paid_by'       => 'company',
            'items'         => [
                ['item_name' => 'Engine Oil', 'quantity' => 6, 'unit' => 'Ltr', 'amount' => 3850],
            ],
        ];

        $this->actingAs($this->user)
            ->post(route('admin.tms.maintenance.bills.store', $this->ownVehicle), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user)
            ->post(route('admin.tms.maintenance.bills.store', $this->rentalVehicle), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $payload['bill_date'] = '2026-06-10';
        $this->actingAs($this->user)
            ->post(route('admin.tms.maintenance.bills.store', $this->ownVehicle), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user)
            ->post(route('admin.tms.maintenance.bills.store', $this->ownVehicle), $payload)
            ->assertSessionHasErrors('bill_no');

        $payload['bill_no'] = ' 14811 ';
        $this->actingAs($this->user)
            ->post(route('admin.tms.maintenance.bills.store', $this->ownVehicle), $payload)
            ->assertSessionHasErrors('bill_no');
    }

    public function test_bill_for_posting_lists_workshop_options(): void
    {
        $this->seedBill($this->ownVehicle, '20001', '2026-06-05', 10350, [['Engine Oil', 10350]]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.posting'))
            ->assertOk()
            ->assertSee('Select workshop')
            ->assertSee('JK Motors', false);
    }

    public function test_bill_for_posting_report_groups_by_vehicle(): void
    {
        $this->seedBill($this->rentalVehicle, '14811', '2026-06-10', 70900, [
            ['Spark Plug', 1400],
            ['Coil', 34000],
        ]);

        $this->seedBill($this->rentalVehicle, '13990', '2026-04-30', 20500, [
            ['Engine Oil', 3850],
            ['Brake Pad', 3500],
        ]);

        $this->seedBill($this->ownVehicle, '20001', '2026-06-05', 10350, [
            ['Engine Oil', 10350],
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.posting', [
                'workshop' => 'JK Motors',
                'from'     => '2026-04-01',
                'to'       => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Bill For Posting')
            ->assertSee('ABC Rent-a-Car Car No: 8402')
            ->assertSee('GM User (GM)')
            ->assertSee('Company Car No: 5678')
            ->assertSee('Sumon Sir')
            ->assertSee('101,750.00');
    }

    public function test_bill_for_posting_works_without_date_filters(): void
    {
        $this->seedBill($this->ownVehicle, '20002', '2026-06-05', 10350, [
            ['Engine Oil', 10350],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.posting', [
                'workshop' => 'JK Motors',
            ]));

        $response->assertOk()
            ->assertSee('Bill For Posting')
            ->assertSee('Company Car No: 5678')
            ->assertSee('10,350.00');

        $this->assertStringNotContainsString('name="from" class="erp-input" value="" required', $response->getContent());
        $this->assertStringNotContainsString('name="to" class="erp-input" value="" required', $response->getContent());
    }

    public function test_fleet_cost_report_includes_maintenance_total(): void
    {
        TmsMaintenanceBill::create([
            'factory_id'    => $this->factory->id,
            'vehicle_id'    => $this->ownVehicle->id,
            'bill_no'       => '30001',
            'bill_date'     => '2026-06-15',
            'workshop_name' => 'JK Motors',
            'total_amount'  => 500,
            'paid_by'       => 'company',
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.reports.index', ['tab' => 'fleet_cost', 'from' => '2026-06-01', 'to' => '2026-06-30']))
            ->assertOk()
            ->assertSee('500.00');
    }

    public function test_maintenance_report_filters_by_workshop(): void
    {
        TmsMaintenanceBill::create([
            'factory_id'    => $this->factory->id,
            'vehicle_id'    => $this->ownVehicle->id,
            'bill_no'       => 'Bill # 1001',
            'bill_date'     => '2026-06-10',
            'workshop_name' => 'Jayma Motors',
            'total_amount'  => 1000,
            'paid_by'       => 'company',
        ]);

        TmsMaintenanceBill::create([
            'factory_id'    => $this->factory->id,
            'vehicle_id'    => $this->ownVehicle->id,
            'bill_no'       => 'Bill # 1002',
            'bill_date'     => '2026-06-12',
            'workshop_name' => 'JK Motors',
            'total_amount'  => 2000,
            'paid_by'       => 'rental_party',
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.reports.index', ['tab' => 'maintenance']))
            ->assertOk()
            ->assertSee('Workshop', false)
            ->assertSee('Jayma Motors')
            ->assertSee('JK Motors')
            ->assertSee('Bill # 1001')
            ->assertSee('Bill # 1002')
            ->assertSee('3,000.00')
            ->assertSee('1,000.00')
            ->assertSee('2,000.00');

        $this->actingAs($this->user)
            ->get(route('admin.tms.reports.index', [
                'tab'      => 'maintenance',
                'workshop' => 'Jayma Motors',
            ]))
            ->assertOk()
            ->assertSee('Bill # 1001')
            ->assertDontSee('Bill # 1002')
            ->assertSee('1,000.00')
            ->assertDontSee('2,000.00');
    }

    public function test_maintenance_register_filters(): void
    {
        $this->seedBill($this->rentalVehicle, '14811', '2026-06-10', 40400, [
            ['Spark Plug', 1400],
            ['Coil', 34000],
        ]);

        $this->seedBill($this->rentalVehicle, '13990', '2026-04-30', 20500, [
            ['Engine Oil', 3850],
            ['Brake Pad', 3500],
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.register', [
                'vehicle' => $this->rentalVehicle,
                'bill_no' => '14811',
            ]))
            ->assertOk()
            ->assertSee('14811')
            ->assertDontSee('13990');

        $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.register', [
                'vehicle' => $this->rentalVehicle,
                'from'    => '2026-06-01',
                'to'      => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('14811')
            ->assertDontSee('13990');

        $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.register', [
                'vehicle'  => $this->rentalVehicle,
                'workshop' => 'JK Motors',
                'item'     => 'Brake',
            ]))
            ->assertOk()
            ->assertSee('13990')
            ->assertDontSee('14811');
    }

    public function test_maintenance_register_print(): void
    {
        $this->seedBill($this->rentalVehicle, '14811', '2026-06-10', 40400, [
            ['Spark Plug', 1400],
            ['Coil', 34000],
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.maintenance.register.print', $this->rentalVehicle))
            ->assertOk()
            ->assertSee('Summary Of Vehicle Maintenance')
            ->assertSee('Month Of: June-2026')
            ->assertSee('14811')
            ->assertSee('Spark Plug')
            ->assertSee('Bill Total')
            ->assertDontSee('Delete');
    }

    /** @param  list<array{0: string, 1: float}>  $items */
    private function seedBill(TmsVehicle $vehicle, string $billNo, string $date, float $total, array $items): TmsMaintenanceBill
    {
        $bill = TmsMaintenanceBill::create([
            'factory_id'    => $this->factory->id,
            'vehicle_id'    => $vehicle->id,
            'bill_no'       => $billNo,
            'bill_date'     => $date,
            'workshop_name' => 'JK Motors',
            'total_amount'  => $total,
            'paid_by'       => 'company',
        ]);

        foreach ($items as $index => [$name, $amount]) {
            $bill->items()->create([
                'item_name'  => $name,
                'amount'     => $amount,
                'sort_order' => $index,
            ]);
        }

        return $bill;
    }
}
