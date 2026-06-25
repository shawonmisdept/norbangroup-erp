<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeeTaxLedger;
use App\Models\Hrm\LoanAccount;
use App\Models\Hrm\LoanInstallment;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\PfAccount;
use App\Models\Hrm\PfContribution;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use App\Models\Hrm\ShiftRoster;
use App\Models\Hrm\ShiftRosterEntry;
use App\Models\Hrm\TaxSlab;
use App\Models\Hrm\TaxYear;
use App\Models\Role;
use App\Models\User;
use App\Services\Hrm\LoanRecoveryService;
use App\Services\Hrm\PayrollProcessor;
use App\Services\Hrm\ShiftRosterService;
use App\Services\Hrm\ShiftRosterVarianceService;
use App\Services\Hrm\TdsCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmPriority4FinanceTest extends TestCase
{
    use RefreshDatabase;

    private function financeAdmin(): User
    {
        $role = Role::create([
            'name'        => 'Finance Admin',
            'permissions' => ['hrm.finance.view', 'hrm.finance.manage', 'hrm.salary.process.manage', 'hrm.salary.process.view', 'hrm.attendance.view', 'hrm.attendance.roster.view', 'hrm.attendance.roster.manage', 'hrm.attendance.manage'],
        ]);

        return User::create([
            'name'     => 'Finance Admin',
            'email'    => 'finance@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    public function test_finance_hub_loads(): void
    {
        $this->actingAs($this->financeAdmin())
            ->get(route('admin.hrm.finance.hub'))
            ->assertOk()
            ->assertSee('Finance & Statutory Deductions');
    }

    public function test_tds_calculator_uses_tax_slabs(): void
    {
        $factory = Factory::create(['name' => 'Tax Factory', 'is_active' => true]);
        $year = TaxYear::create([
            'factory_id' => $factory->id,
            'label'      => '2025-26',
            'start_date' => '2025-07-01',
            'end_date'   => '2026-06-30',
            'is_active'  => true,
        ]);
        TaxSlab::create(['tax_year_id' => $year->id, 'min_income' => 0, 'max_income' => 350000, 'rate_percent' => 0, 'sort_order' => 0]);
        TaxSlab::create(['tax_year_id' => $year->id, 'min_income' => 350001, 'max_income' => null, 'rate_percent' => 10, 'sort_order' => 1]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'TAX-1',
            'name'          => 'Tax Worker',
            'status'        => 'active',
        ]);
        $structure = SalaryStructure::create([
            'factory_id'     => $factory->id,
            'employee_id'    => $employee->id,
            'pay_type'       => 'salary',
            'basic_salary'   => 50000,
            'gross_salary'   => 50000,
            'is_active'      => true,
            'payment_method' => 'bank',
        ]);

        $result = app(TdsCalculator::class)->monthlyTds(
            $employee,
            50000,
            50000,
            $structure,
            Carbon::parse('2026-01-15')
        );

        $this->assertGreaterThan(0, $result['tds_amount']);
    }

    public function test_pf_and_loan_deducted_in_payroll(): void
    {
        $factory = Factory::create(['name' => 'Pay Factory', 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'PF-1',
            'name'          => 'PF Worker',
            'status'        => 'active',
        ]);
        SalaryStructure::create([
            'factory_id'     => $factory->id,
            'employee_id'    => $employee->id,
            'pay_type'       => 'salary',
            'basic_salary'   => 26000,
            'gross_salary'   => 26000,
            'is_active'      => true,
            'payment_method' => 'bank',
        ]);
        PfAccount::create([
            'factory_id'        => $factory->id,
            'employee_id'       => $employee->id,
            'employee_rate_pct' => 7,
            'employer_rate_pct' => 7.5,
            'balance'           => 0,
            'is_active'         => true,
        ]);

        $loan = LoanAccount::create([
            'factory_id'         => $factory->id,
            'employee_id'        => $employee->id,
            'loan_type'          => 'advance',
            'principal'          => 3000,
            'balance'            => 3000,
            'emi_amount'         => 1000,
            'total_installments' => 3,
            'paid_installments'  => 0,
            'status'             => 'active',
            'approved_at'        => now(),
        ]);
        \App\Models\Hrm\LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_no'  => 1,
            'due_date'        => now()->endOfMonth(),
            'amount'          => 1000,
            'status'          => 'pending',
        ]);

        $attendance = AttendancePeriod::create([
            'factory_id' => $factory->id,
            'year'       => now()->year,
            'month'      => now()->month,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date'   => now()->endOfMonth()->toDateString(),
            'status'     => 'frozen',
            'frozen_at'  => now(),
        ]);
        $payroll = PayrollPeriod::create([
            'factory_id'           => $factory->id,
            'year'                 => now()->year,
            'month'                => now()->month,
            'start_date'           => now()->startOfMonth(),
            'end_date'             => now()->endOfMonth(),
            'attendance_period_id' => $attendance->id,
            'status'               => 'draft',
        ]);

        app(PayrollProcessor::class)->calculatePeriod($payroll, $this->financeAdmin());

        $item = $payroll->fresh()->items()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($item);
        $this->assertGreaterThan(0, (float) $item->pf_employee_amount);
        $this->assertGreaterThan(0, (float) $item->loan_deduction);
    }

    public function test_shift_roster_overrides_default_shift(): void
    {
        $factory = Factory::create(['name' => 'Roster Factory', 'is_active' => true]);
        $defaultShift = Shift::create(['factory_id' => $factory->id, 'code' => 'SFT-A', 'name' => 'Day', 'start_time' => '08:00', 'end_time' => '17:00', 'is_active' => true]);
        $nightShift = Shift::create(['factory_id' => $factory->id, 'code' => 'SFT-B', 'name' => 'Night', 'start_time' => '20:00', 'end_time' => '05:00', 'is_night' => true, 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'R-1',
            'name'          => 'Roster Worker',
            'status'        => 'active',
            'shift_id'      => $defaultShift->id,
        ]);
        $roster = ShiftRoster::create([
            'factory_id' => $factory->id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addDays(6)->toDateString(),
            'status'     => 'published',
        ]);
        ShiftRosterEntry::create([
            'roster_id'   => $roster->id,
            'employee_id' => $employee->id,
            'roster_date' => now()->toDateString(),
            'shift_id'    => $nightShift->id,
        ]);

        $resolved = app(ShiftRosterService::class)->resolveShift($employee, now());

        $this->assertSame($nightShift->id, $resolved?->id);
    }

    public function test_loan_emi_is_auto_calculated_from_principal_and_installments(): void
    {
        $this->assertSame(1000.0, LoanAccount::calculateEmi(3000, 3));
        $this->assertSame(333.33, LoanAccount::calculateEmi(1000, 3));
        $this->assertSame(5000.0, LoanAccount::calculateEmi(5000, 1));
    }

    public function test_bulk_festival_advance_creates_approved_loans_with_schedule(): void
    {
        $factory = Factory::create(['name' => 'Advance Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $admin->update(['factory_id' => $factory->id]);

        $employees = collect(['ADV-1', 'ADV-2'])->map(fn ($code) => Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => $code,
            'name'          => "Worker {$code}",
            'status'        => 'active',
        ]));

        $response = $this->actingAs($admin)->post(route('admin.hrm.finance.loans.bulk.store'), [
            'factory_id'         => $factory->id,
            'default_amount'     => 5000,
            'total_installments' => 2,
            'notes'              => 'Eid-ul-Fitr 2026 advance',
            'auto_approve'       => 1,
            'employee_ids'       => $employees->pluck('id')->all(),
        ]);

        $response->assertRedirect(route('admin.hrm.finance.loans.index'));
        $response->assertSessionHas('success');

        $this->assertSame(2, LoanAccount::query()->where('factory_id', $factory->id)->count());

        foreach ($employees as $employee) {
            $loan = LoanAccount::query()->where('employee_id', $employee->id)->first();
            $this->assertNotNull($loan);
            $this->assertSame('advance', $loan->loan_type);
            $this->assertSame('active', $loan->status);
            $this->assertSame(5000.0, (float) $loan->principal);
            $this->assertSame(2500.0, (float) $loan->emi_amount);
            $this->assertSame(2, $loan->installments()->count());
        }
    }

    public function test_loan_early_settlement_closes_active_loan(): void
    {
        $factory = Factory::create(['name' => 'Settle Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'SET-1',
            'name'          => 'Settle Worker',
            'status'        => 'active',
        ]);
        $loan = LoanAccount::create([
            'factory_id'         => $factory->id,
            'employee_id'        => $employee->id,
            'loan_type'          => 'advance',
            'principal'          => 6000,
            'balance'            => 6000,
            'emi_amount'         => 2000,
            'total_installments' => 3,
            'paid_installments'  => 0,
            'status'             => 'active',
            'approved_at'        => now(),
        ]);
        foreach (app(LoanRecoveryService::class)->buildSchedule($loan) as $installment) {
            $installment->save();
        }

        $this->actingAs($admin)->post(route('admin.hrm.finance.loans.settle', $loan), [
            'settlement_amount' => 6000,
            'notes'             => 'Full early payment',
        ])->assertRedirect(route('admin.hrm.finance.loans.show', $loan));

        $loan->refresh();
        $this->assertSame('closed', $loan->status);
        $this->assertSame(0.0, (float) $loan->balance);
        $this->assertSame(0, $loan->installments()->where('status', 'pending')->count());
    }

    public function test_tax_certificate_prints_for_employee(): void
    {
        $factory = Factory::create(['name' => 'Cert Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $year = TaxYear::create([
            'factory_id' => $factory->id,
            'label'      => '2025-26',
            'start_date' => '2025-07-01',
            'end_date'   => '2026-06-30',
            'is_active'  => true,
        ]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'CERT-1',
            'name'          => 'Cert Worker',
            'status'        => 'active',
        ]);
        EmployeeTaxLedger::create([
            'factory_id'      => $factory->id,
            'employee_id'     => $employee->id,
            'tax_year_id'     => $year->id,
            'year'            => 2026,
            'month'           => 1,
            'taxable_income'  => 40000,
            'tds_amount'      => 500,
        ]);

        $this->actingAs($admin)->get(route('admin.hrm.finance.tax.certificate', [
            'employee_id' => $employee->id,
            'tax_year_id' => $year->id,
        ]))->assertOk()->assertSee('Income Tax (TDS) Certificate');
    }

    public function test_pf_employer_report_page_loads(): void
    {
        $factory = Factory::create(['name' => 'PF Report Factory', 'is_active' => true]);

        $this->actingAs($this->financeAdmin())
            ->get(route('admin.hrm.finance.pf.employer-report', ['factory_id' => $factory->id]))
            ->assertOk()
            ->assertSee('PF Employer Contribution Report');
    }

    public function test_tax_year_can_be_updated(): void
    {
        $factory = Factory::create(['name' => 'Edit Tax Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $year = TaxYear::create([
            'factory_id' => $factory->id,
            'label'      => '2025-26',
            'start_date' => '2025-07-01',
            'end_date'   => '2026-06-30',
            'is_active'  => true,
        ]);
        TaxSlab::create(['tax_year_id' => $year->id, 'min_income' => 0, 'max_income' => 350000, 'rate_percent' => 0, 'sort_order' => 0]);

        $this->actingAs($admin)->put(route('admin.hrm.finance.tax.update', $year), [
            'label'      => '2025-26 Revised',
            'start_date' => '2025-07-01',
            'end_date'   => '2026-06-30',
            'is_active'  => 1,
            'slabs'      => [
                ['min_income' => 0, 'max_income' => 400000, 'rate_percent' => 0],
                ['min_income' => 400001, 'max_income' => null, 'rate_percent' => 10],
            ],
        ])->assertRedirect(route('admin.hrm.finance.tax.index', ['factory_id' => $factory->id]));

        $year->refresh();
        $this->assertSame('2025-26 Revised', $year->label);
        $this->assertSame(2, $year->slabs()->count());
    }

    public function test_pending_loan_can_be_rejected(): void
    {
        $factory = Factory::create(['name' => 'Reject Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'REJ-1',
            'name'          => 'Reject Worker',
            'status'        => 'active',
        ]);
        $loan = LoanAccount::create([
            'factory_id'         => $factory->id,
            'employee_id'        => $employee->id,
            'loan_type'          => 'advance',
            'principal'          => 5000,
            'balance'            => 5000,
            'emi_amount'         => 2500,
            'total_installments' => 2,
            'paid_installments'  => 0,
            'status'             => 'pending',
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.finance.loans.reject', $loan), [
            'reject_reason' => 'Not eligible',
        ])->assertRedirect(route('admin.hrm.finance.loans.index'));

        $loan->refresh();
        $this->assertSame('rejected', $loan->status);
        $this->assertSame(0.0, (float) $loan->balance);
    }

    public function test_employee_can_apply_for_loan_via_portal(): void
    {
        $factory = Factory::create(['name' => 'Portal Loan Factory', 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'PL-1',
            'name'          => 'Portal Worker',
            'status'        => 'active',
        ]);
        $portalUser = \App\Models\Hrm\EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);

        $this->actingAs($portalUser, 'employee')->post(route('employee.loans.apply.store'), [
            'loan_type'          => 'advance',
            'principal'          => 3000,
            'total_installments' => 3,
            'notes'              => 'Festival advance',
        ])->assertRedirect(route('employee.loans'));

        $this->assertDatabaseHas('hrm_loan_accounts', [
            'employee_id' => $employee->id,
            'status'      => 'pending',
            'principal'   => 3000,
        ]);
    }

    public function test_hrm_dashboard_shows_finance_kpis_for_finance_admin(): void
    {
        $this->actingAs($this->financeAdmin())
            ->get(route('admin.hrm.dashboard'))
            ->assertOk()
            ->assertSee('Finance Overview');
    }

    public function test_pf_account_show_displays_contribution_history(): void
    {
        $factory = Factory::create(['name' => 'PF Show Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'PF-SHOW-1',
            'name'          => 'PF Show Worker',
            'status'        => 'active',
        ]);
        $account = PfAccount::create([
            'factory_id'        => $factory->id,
            'employee_id'       => $employee->id,
            'employee_rate_pct' => 7,
            'employer_rate_pct' => 7.5,
            'balance'           => 1500,
            'is_active'         => true,
            'opened_at'         => now()->toDateString(),
        ]);
        PfContribution::create([
            'pf_account_id'   => $account->id,
            'year'            => 2026,
            'month'           => 1,
            'base_amount'     => 10000,
            'employee_amount' => 700,
            'employer_amount' => 750,
        ]);

        $this->actingAs($admin)->get(route('admin.hrm.finance.pf.show', $account))
            ->assertOk()
            ->assertSee('Contribution History')
            ->assertSee('PF-SHOW-1')
            ->assertSee('700.00');
    }

    public function test_loan_statement_prints_for_admin_and_employee(): void
    {
        $factory = Factory::create(['name' => 'Stmt Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'STMT-1',
            'name'          => 'Statement Worker',
            'status'        => 'active',
        ]);
        $portalUser = \App\Models\Hrm\EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);
        $loan = LoanAccount::create([
            'factory_id'         => $factory->id,
            'employee_id'        => $employee->id,
            'loan_type'          => 'advance',
            'principal'          => 3000,
            'balance'            => 2000,
            'emi_amount'         => 1000,
            'total_installments' => 3,
            'paid_installments'  => 1,
            'status'             => 'active',
            'approved_at'        => now(),
        ]);
        LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_no'  => 1,
            'due_date'        => now()->addMonth(),
            'amount'          => 1000,
            'status'          => 'pending',
        ]);

        $this->actingAs($admin)->get(route('admin.hrm.finance.loans.statement', $loan))
            ->assertOk()
            ->assertSee('Loan / Advance Statement')
            ->assertSee('STMT-1');

        $this->actingAs($portalUser, 'employee')->get(route('employee.loans.statement', $loan))
            ->assertOk()
            ->assertSee('Loan / Advance Statement');
    }

    public function test_annual_tds_export_downloads_csv(): void
    {
        $factory = Factory::create(['name' => 'Export Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $year = TaxYear::create([
            'factory_id' => $factory->id,
            'label'      => '2025-26',
            'start_date' => '2025-07-01',
            'end_date'   => '2026-06-30',
            'is_active'  => true,
        ]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'TDS-EXP-1',
            'name'          => 'TDS Export Worker',
            'status'        => 'active',
        ]);
        EmployeeTaxLedger::create([
            'factory_id'     => $factory->id,
            'employee_id'    => $employee->id,
            'tax_year_id'    => $year->id,
            'year'           => 2026,
            'month'          => 1,
            'taxable_income' => 40000,
            'tds_amount'     => 500,
        ]);
        EmployeeTaxLedger::create([
            'factory_id'     => $factory->id,
            'employee_id'    => $employee->id,
            'tax_year_id'    => $year->id,
            'year'           => 2026,
            'month'          => 2,
            'taxable_income' => 42000,
            'tds_amount'     => 600,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.hrm.finance.tax.export-annual', [
            'factory_id'  => $factory->id,
            'tax_year_id' => $year->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('TDS-EXP-1', $response->streamedContent());
        $this->assertStringContainsString('82000.00', $response->streamedContent());
        $this->assertStringContainsString('1100.00', $response->streamedContent());
    }

    public function test_roster_variance_report_lists_missing_attendance(): void
    {
        $factory = Factory::create(['name' => 'Variance Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $shift = Shift::create(['factory_id' => $factory->id, 'code' => 'SFT-A', 'name' => 'Day', 'start_time' => '08:00', 'end_time' => '17:00', 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'VAR-1',
            'name'          => 'Variance Worker',
            'status'        => 'active',
            'shift_id'      => $shift->id,
        ]);
        $roster = ShiftRoster::create([
            'factory_id' => $factory->id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addDays(6)->toDateString(),
            'status'     => 'published',
        ]);
        ShiftRosterEntry::create([
            'roster_id'   => $roster->id,
            'employee_id' => $employee->id,
            'roster_date' => now()->toDateString(),
            'shift_id'    => $shift->id,
        ]);

        $rows = app(ShiftRosterVarianceService::class)->buildReport($factory->id, $roster->id);
        $this->assertCount(1, $rows);
        $this->assertSame('no_attendance', $rows->first()['variance_type']);

        $this->actingAs($admin)->get(route('admin.hrm.attendance.roster.variance', [
            'factory_id' => $factory->id,
            'roster_id'  => $roster->id,
        ]))->assertOk()->assertSee('No attendance')->assertSee('VAR-1');
    }

    public function test_publishing_roster_notifies_employee_portal_users(): void
    {
        $factory = Factory::create(['name' => 'Notify Factory', 'is_active' => true]);
        $admin = $this->financeAdmin();
        $shift = Shift::create(['factory_id' => $factory->id, 'code' => 'SFT-A', 'name' => 'Day', 'start_time' => '08:00', 'end_time' => '17:00', 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'NOTIF-1',
            'name'          => 'Notify Worker',
            'status'        => 'active',
            'shift_id'      => $shift->id,
        ]);
        $portalUser = \App\Models\Hrm\EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => bcrypt('password'),
            'is_active'   => true,
        ]);
        $roster = ShiftRoster::create([
            'factory_id' => $factory->id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addDays(6)->toDateString(),
            'status'     => 'draft',
        ]);
        ShiftRosterEntry::create([
            'roster_id'   => $roster->id,
            'employee_id' => $employee->id,
            'roster_date' => now()->toDateString(),
            'shift_id'    => $shift->id,
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.attendance.roster.publish', $roster))
            ->assertRedirect();

        $portalUser->refresh();
        $this->assertTrue($portalUser->notifications()->where('type', \App\Notifications\PortalRosterPublishedNotification::class)->exists());
    }
}
