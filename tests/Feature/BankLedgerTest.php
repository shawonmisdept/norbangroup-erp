<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryBank;
use App\Models\Hrm\Shift;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $user;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create(['name' => 'Ledger Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Accounts Ledger',
            'permissions' => ['hrm.salary.close.view', 'hrm.salary.close.manage', 'hrm.salary.approve'],
        ]);

        $this->user = User::create([
            'name'     => 'Accounts',
            'email'    => 'accounts-ledger@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->period = PayrollPeriod::create([
            'factory_id' => $this->factory->id,
            'year'       => 2026,
            'month'      => 6,
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'frozen',
            'frozen_at'  => now(),
        ]);

        $shift = Shift::create([
            'factory_id' => $this->factory->id, 'name' => 'Day', 'start_time' => '09:00:00',
            'end_time' => '17:00:00', 'break_minutes' => 60, 'is_active' => true,
        ]);

        $sjib = SalaryBank::create([
            'factory_id' => $this->factory->id, 'code' => 'SJIB',
            'name' => 'Shahjalal Islami Bank PLC', 'short_name' => 'Shahjalal', 'is_active' => true,
        ]);

        $brac = SalaryBank::create([
            'factory_id' => $this->factory->id, 'code' => 'BRAC',
            'name' => 'BRAC Bank PLC', 'short_name' => 'BRAC Bank', 'is_active' => true,
        ]);

        $emp1 = Employee::create([
            'factory_id' => $this->factory->id, 'shift_id' => $shift->id,
            'employee_code' => 'LG-1', 'name' => 'Bank Worker One', 'status' => 'active',
        ]);

        $emp2 = Employee::create([
            'factory_id' => $this->factory->id, 'shift_id' => $shift->id,
            'employee_code' => 'LG-2', 'name' => 'Bank Worker Two', 'status' => 'active',
        ]);

        $emp3 = Employee::create([
            'factory_id' => $this->factory->id, 'shift_id' => $shift->id,
            'employee_code' => 'LG-3', 'name' => 'Unassigned Worker', 'status' => 'active',
        ]);

        PayrollItem::create([
            'factory_id' => $this->factory->id, 'payroll_period_id' => $this->period->id,
            'employee_id' => $emp1->id, 'pay_type' => 'salary', 'net_pay' => 42000,
            'bank_pay_amount' => 25000, 'cash_pay_amount' => 17000,
            'salary_bank_id' => $sjib->id, 'bank_account' => '1111111111', 'payment_method' => 'split',
            'cash_disbursed_at' => now(),
        ]);

        PayrollItem::create([
            'factory_id' => $this->factory->id, 'payroll_period_id' => $this->period->id,
            'employee_id' => $emp2->id, 'pay_type' => 'salary', 'net_pay' => 30000,
            'bank_pay_amount' => 30000, 'cash_pay_amount' => 0,
            'salary_bank_id' => $brac->id, 'bank_account' => '2222222222', 'payment_method' => 'bank',
        ]);

        PayrollItem::create([
            'factory_id' => $this->factory->id, 'payroll_period_id' => $this->period->id,
            'employee_id' => $emp3->id, 'pay_type' => 'salary', 'net_pay' => 20000,
            'bank_pay_amount' => 15000, 'cash_pay_amount' => 5000,
            'salary_bank_id' => null, 'bank_account' => '3333333333', 'payment_method' => 'split',
            'cash_disbursed_at' => now(),
        ]);
    }

    public function test_bank_ledger_shows_summary_and_unassigned_warning(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.hrm.salary.bank-ledger.index', [
                'payroll_period_id' => $this->period->id,
            ]))
            ->assertOk()
            ->assertSee('Bank Payment Register')
            ->assertSee('Shahjalal')
            ->assertSee('BRAC Bank')
            ->assertSee('Unassigned Bank')
            ->assertSee('Unassigned Worker')
            ->assertSee('employee(s) have bank pay but no salary bank assigned')
            ->assertSee('← Salary Close')
            ->assertSee('Bank Advise CSV')
            ->assertSee('Cash CSV')
            ->assertSee('Print')
            ->assertSee('Cash Status')
            ->assertSee('Disbursed');
    }

    public function test_calculated_period_excluded_from_ledger(): void
    {
        $draft = PayrollPeriod::create([
            'factory_id' => $this->factory->id,
            'year'       => 2026,
            'month'      => 5,
            'start_date' => '2026-05-01',
            'end_date'   => '2026-05-31',
            'status'     => 'calculated',
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.hrm.salary.bank-ledger.export-summary', [
                'payroll_period_id' => $draft->id,
            ]))
            ->assertStatus(422);
    }

    public function test_bank_ledger_exports_csv(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.hrm.salary.bank-ledger.export-summary', [
                'payroll_period_id' => $this->period->id,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($this->user)
            ->get(route('admin.hrm.salary.bank-ledger.export-detail', [
                'payroll_period_id' => $this->period->id,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
