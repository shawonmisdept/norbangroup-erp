<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\FinalSettlement;
use App\Models\Hrm\LeaveBalance;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\LoanAccount;
use App\Models\Hrm\PfAccount;
use App\Models\Hrm\SalaryStructure;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\FinalSettlementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmFinalSettlementTest extends TestCase
{
    use RefreshDatabase;

    private function financeAdmin(): User
    {
        $role = Role::create([
            'name'        => 'Settlement Admin',
            'permissions' => [
                'hrm.finance.view',
                'hrm.finance.manage',
                'hrm.finance.settlement.view',
                'hrm.finance.settlement.manage',
                'hrm.compliance.view',
                'hrm.compliance.manage',
            ],
        ]);

        return User::create([
            'name'     => 'Settlement Admin',
            'email'    => 'settlement@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    private function separatedEmployee(Factory $factory): Employee
    {
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'FNF-1',
            'name'          => 'Exit Worker',
            'status'        => 'resigned',
            'joining_date'  => now()->subYears(6)->toDateString(),
        ]);

        SalaryStructure::create([
            'factory_id'     => $factory->id,
            'employee_id'    => $employee->id,
            'pay_type'       => 'salary',
            'gross_salary'   => 30000,
            'basic_salary'   => 30000,
            'is_active'      => true,
            'payment_method' => 'bank',
        ]);

        PfAccount::create([
            'factory_id'        => $factory->id,
            'employee_id'       => $employee->id,
            'employee_rate_pct' => 7,
            'employer_rate_pct' => 7.5,
            'balance'           => 5000,
            'is_active'         => true,
            'opened_at'         => now()->subYear()->toDateString(),
        ]);

        LoanAccount::create([
            'factory_id'         => $factory->id,
            'employee_id'        => $employee->id,
            'loan_type'          => 'advance',
            'principal'          => 2000,
            'balance'            => 2000,
            'emi_amount'         => 1000,
            'total_installments' => 2,
            'paid_installments'  => 0,
            'status'             => 'active',
            'approved_at'        => now(),
        ]);

        $leaveType = LeaveType::create([
            'code'              => 'LVT-AL',
            'name'              => 'Annual Leave',
            'is_paid'           => true,
            'max_days_per_year' => 18,
            'is_active'         => true,
        ]);

        LeaveBalance::create([
            'factory_id'    => $factory->id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year'          => now()->year,
            'entitled_days' => 10,
            'used_days'     => 2,
            'pending_days'  => 0,
        ]);

        return $employee;
    }

    public function test_final_settlement_index_loads(): void
    {
        $this->actingAs($this->financeAdmin())
            ->get(route('admin.hrm.finance.final-settlement.index'))
            ->assertOk()
            ->assertSee('Final Settlement');
    }

    public function test_final_settlement_calculates_net_payable(): void
    {
        $factory = Factory::create(['name' => 'FNF Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $employee = $this->separatedEmployee($factory);
        $lastDay = Carbon::parse('2026-06-15');

        $settlement = app(FinalSettlementService::class)->createDraft($employee, $admin, $lastDay);
        $settlement = app(FinalSettlementService::class)->calculate($settlement, $admin);

        $this->assertSame('calculated', $settlement->status);
        $this->assertGreaterThan(0, (float) $settlement->gratuity_amount);
        $this->assertSame(5000.0, (float) $settlement->pf_withdrawal);
        $this->assertSame(2000.0, (float) $settlement->loan_deduction);
        $this->assertGreaterThan(0, (float) $settlement->leave_encashment);
        $this->assertGreaterThan(0, (float) $settlement->unpaid_salary);
        $this->assertGreaterThan(0, (float) $settlement->net_payable);
    }

    public function test_final_settlement_workflow_through_paid(): void
    {
        $factory = Factory::create(['name' => 'FNF Flow Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $employee = $this->separatedEmployee($factory);

        $settlement = app(FinalSettlementService::class)->createDraft($employee, $admin, Carbon::today());
        $settlement = app(FinalSettlementService::class)->calculate($settlement, $admin);

        app(FinalSettlementService::class)->updateClearance($settlement, [
            'hr' => true, 'it' => true, 'stores' => true, 'accounts' => true, 'line_chief' => true,
        ]);

        $settlement = app(FinalSettlementService::class)->approve($settlement->fresh(), $admin);
        $this->assertSame('approved', $settlement->status);

        $settlement = app(FinalSettlementService::class)->markPaid($settlement, $admin);
        $this->assertSame('paid', $settlement->status);

        $loan = LoanAccount::where('employee_id', $employee->id)->first();
        $this->assertSame('closed', $loan->status);
        $this->assertSame(0.0, (float) $loan->balance);

        $pf = PfAccount::where('employee_id', $employee->id)->first();
        $this->assertFalse((bool) $pf->is_active);
    }

    public function test_final_settlement_print_view_loads(): void
    {
        $factory = Factory::create(['name' => 'FNF Print Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $employee = $this->separatedEmployee($factory);

        $settlement = app(FinalSettlementService::class)->createDraft($employee, $admin, Carbon::today());
        app(FinalSettlementService::class)->calculate($settlement, $admin);

        $this->actingAs($admin)->get(route('admin.hrm.finance.final-settlement.print', $settlement))
            ->assertOk()
            ->assertSee('Settlement Sheet');
    }

    public function test_cannot_create_settlement_for_active_employee(): void
    {
        $factory = Factory::create(['name' => 'Active Factory', 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'ACT-1',
            'name'          => 'Active Worker',
            'status'        => 'active',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        app(FinalSettlementService::class)->createDraft($employee, $this->financeAdmin(), Carbon::today());
    }

    public function test_settlement_view_only_user_cannot_create(): void
    {
        $role = Role::create([
            'name'        => 'F&F Viewer',
            'permissions' => ['hrm.finance.settlement.view'],
        ]);
        $viewer = User::create([
            'name'     => 'F&F Viewer',
            'email'    => 'fnf-view@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($viewer)->get(route('admin.hrm.finance.final-settlement.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.hrm.finance.final-settlement.create'))->assertForbidden();
    }
}
