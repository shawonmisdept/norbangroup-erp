<?php

namespace Tests\Feature;

use App\Models\Buyer;
use App\Models\Factory;
use App\Models\Hrm\Building;
use App\Models\Hrm\BuyerHoliday;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Floor;
use App\Models\Hrm\GatePass;
use App\Models\Hrm\Line;
use App\Models\Hrm\ManpowerPlan;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryHold;
use App\Models\Hrm\WorkerTransfer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmRmgExtrasTest extends TestCase
{
    use RefreshDatabase;

    private User $rmgAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name'        => 'RMG Admin',
            'permissions' => ['hrm.rmg.view', 'hrm.rmg.manage'],
        ]);

        $this->rmgAdmin = User::create([
            'name'     => 'RMG Admin',
            'email'    => 'rmg-admin@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    private function factoryWithLines(): array
    {
        $factory = Factory::create(['name' => 'RMG Factory', 'is_active' => true]);
        $building = Building::create(['factory_id' => $factory->id, 'name' => 'Main', 'is_active' => true]);
        $floor = Floor::create(['factory_id' => $factory->id, 'building_id' => $building->id, 'name' => 'F1', 'is_active' => true]);
        $lineA = Line::create(['factory_id' => $factory->id, 'floor_id' => $floor->id, 'name' => 'Line A', 'is_active' => true]);
        $lineB = Line::create(['factory_id' => $factory->id, 'floor_id' => $floor->id, 'name' => 'Line B', 'is_active' => true]);

        return compact('factory', 'building', 'floor', 'lineA', 'lineB');
    }

    public function test_rmg_hub_loads(): void
    {
        $this->actingAs($this->rmgAdmin)
            ->get(route('admin.hrm.rmg.hub'))
            ->assertOk()
            ->assertSee('RMG Extras');
    }

    public function test_worker_transfer_approve_updates_employee(): void
    {
        $ctx = $this->factoryWithLines();
        $employee = Employee::create([
            'factory_id'    => $ctx['factory']->id,
            'line_id'       => $ctx['lineA']->id,
            'employee_code' => 'WT-1',
            'name'          => 'Transfer Worker',
            'status'        => 'active',
        ]);

        $transfer = WorkerTransfer::create([
            'factory_id'      => $ctx['factory']->id,
            'employee_id'     => $employee->id,
            'to_factory_id'   => $ctx['factory']->id,
            'to_line_id'      => $ctx['lineB']->id,
            'effective_date'  => now()->toDateString(),
            'status'          => 'pending',
        ]);

        $this->actingAs($this->rmgAdmin)
            ->post(route('admin.hrm.rmg.worker-transfer.approve', $transfer))
            ->assertRedirect(route('admin.hrm.rmg.worker-transfer.index'));

        $employee->refresh();
        $transfer->refresh();

        $this->assertSame($ctx['lineB']->id, $employee->line_id);
        $this->assertSame('approved', $transfer->status);
    }

    public function test_gate_pass_approve(): void
    {
        $ctx = $this->factoryWithLines();
        $employee = Employee::create([
            'factory_id'    => $ctx['factory']->id,
            'employee_code' => 'GP-1',
            'name'          => 'Gate Worker',
            'status'        => 'active',
        ]);

        $pass = GatePass::create([
            'factory_id'  => $ctx['factory']->id,
            'employee_id' => $employee->id,
            'pass_date'   => now()->toDateString(),
            'status'      => 'pending',
        ]);

        $this->actingAs($this->rmgAdmin)
            ->post(route('admin.hrm.rmg.gate-pass.approve', $pass))
            ->assertRedirect(route('admin.hrm.rmg.gate-pass.index'));

        $this->assertSame('approved', $pass->fresh()->status);
    }

    public function test_manpower_plan_store(): void
    {
        $ctx = $this->factoryWithLines();

        $this->actingAs($this->rmgAdmin)
            ->post(route('admin.hrm.rmg.manpower-planning.store'), [
                'factory_id'     => $ctx['factory']->id,
                'line_id'        => $ctx['lineA']->id,
                'plan_date'      => now()->toDateString(),
                'required_count' => 25,
                'notes'          => 'Peak season',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('hrm_manpower_plans', [
            'factory_id'     => $ctx['factory']->id,
            'line_id'        => $ctx['lineA']->id,
            'required_count' => 25,
        ]);
    }

    public function test_salary_hold_crud_and_release(): void
    {
        $ctx = $this->factoryWithLines();
        $employee = Employee::create([
            'factory_id'    => $ctx['factory']->id,
            'employee_code' => 'SH-1',
            'name'          => 'Hold Worker',
            'status'        => 'active',
        ]);

        $this->actingAs($this->rmgAdmin)
            ->post(route('admin.hrm.rmg.salary-hold.store'), [
                'factory_id'  => $ctx['factory']->id,
                'employee_id' => $employee->id,
                'reason'      => 'Investigation',
                'hold_from'   => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.hrm.rmg.salary-hold.index'));

        $hold = SalaryHold::first();
        $this->assertNotNull($hold);
        $this->assertSame('active', $hold->status);

        $this->actingAs($this->rmgAdmin)
            ->post(route('admin.hrm.rmg.salary-hold.release', $hold))
            ->assertRedirect(route('admin.hrm.rmg.salary-hold.index'));

        $this->assertSame('released', $hold->fresh()->status);
    }

    public function test_buyer_holiday_crud(): void
    {
        $ctx = $this->factoryWithLines();
        $buyer = Buyer::create(['name' => 'H&M', 'is_active' => true]);

        $this->actingAs($this->rmgAdmin)
            ->post(route('admin.hrm.rmg.buyer-holiday.store'), [
                'factory_id' => $ctx['factory']->id,
                'buyer_id'   => $buyer->id,
                'name'       => 'Buyer Shutdown',
                'date'       => '2026-12-25',
                'is_active'  => 1,
            ])
            ->assertRedirect(route('admin.hrm.rmg.buyer-holiday.index'));

        $this->assertDatabaseHas('hrm_buyer_holidays', [
            'factory_id' => $ctx['factory']->id,
            'buyer_id'   => $buyer->id,
            'name'       => 'Buyer Shutdown',
        ]);

        $holiday = BuyerHoliday::first();
        $this->actingAs($this->rmgAdmin)
            ->get(route('admin.hrm.rmg.buyer-holiday.index'))
            ->assertOk()
            ->assertSee('Buyer Shutdown');

        $holiday->delete();
        $this->assertDatabaseMissing('hrm_buyer_holidays', ['id' => $holiday->id]);
    }

    public function test_cash_list_export(): void
    {
        $ctx = $this->factoryWithLines();
        $employee = Employee::create([
            'factory_id'    => $ctx['factory']->id,
            'line_id'       => $ctx['lineA']->id,
            'employee_code' => 'CASH-1',
            'name'          => 'Cash Worker',
            'status'        => 'active',
        ]);

        $period = PayrollPeriod::create([
            'factory_id' => $ctx['factory']->id,
            'year'       => 2026,
            'month'      => 1,
            'start_date' => '2026-01-01',
            'end_date'   => '2026-01-31',
            'status'     => 'calculated',
        ]);

        PayrollItem::create([
            'factory_id'        => $ctx['factory']->id,
            'payroll_period_id' => $period->id,
            'employee_id'       => $employee->id,
            'pay_type'          => 'salary',
            'gross_pay'         => 15000,
            'net_pay'           => 15000,
        ]);

        $this->actingAs($this->rmgAdmin)
            ->get(route('admin.hrm.rmg.cash-list.export', ['payroll_period_id' => $period->id]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_buyer_audit_export(): void
    {
        $ctx = $this->factoryWithLines();

        $this->actingAs($this->rmgAdmin)
            ->get(route('admin.hrm.rmg.buyer-audit-export.export', [
                'factory_id' => $ctx['factory']->id,
                'year'       => 2026,
                'month'      => 1,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
