<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\ContractRenewal;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\EmployeePromotion;
use App\Models\Hrm\EmployeeSeparation;
use App\Models\Hrm\FinalSettlement;
use App\Models\Hrm\IssuedLetter;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\LeaveApproval;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryIncrementLog;
use App\Models\Hrm\SalaryStructure;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePortalEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private Employee $employee;

    private EmployeePortalUser $portalUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create(['name' => 'Portal Factory', 'is_active' => true]);
        $department = Department::create(['name' => 'HR', 'factory_id' => $this->factory->id, 'is_active' => true]);
        $designation = Designation::create(['name' => 'Officer', 'department_id' => $department->id, 'is_active' => true]);
        $grade = SalaryGrade::create(['factory_id' => $this->factory->id, 'code' => 'G3', 'name' => 'G-3', 'is_active' => true]);

        $this->employee = Employee::create([
            'factory_id'        => $this->factory->id,
            'department_id'     => $department->id,
            'designation_id'    => $designation->id,
            'employee_code'     => 'PF-1001',
            'name'              => 'Portal Worker',
            'email'             => 'worker@test.com',
            'nid_number'        => '1234567890',
            'joining_date'      => now()->subYears(2)->toDateString(),
            'contract_end_date' => now()->addMonths(2)->toDateString(),
            'status'            => 'active',
        ]);

        SalaryStructure::create([
            'factory_id'       => $this->factory->id,
            'employee_id'      => $this->employee->id,
            'salary_grade_id'  => $grade->id,
            'gross_salary'     => 30000,
            'pay_type'         => 'salary',
            'payment_method'   => 'bank',
            'effective_from'   => now()->subYear()->toDateString(),
            'is_active'        => true,
        ]);

        $this->portalUser = EmployeePortalUser::create([
            'employee_id' => $this->employee->id,
            'password'    => 'secret-password',
            'is_active'   => true,
        ]);
    }

    public function test_profile_shows_official_info_and_letters(): void
    {
        IssuedLetter::create([
            'factory_id'   => $this->factory->id,
            'employee_id'  => $this->employee->id,
            'letter_type'  => 'experience',
            'reference_no' => 'EXP-001',
            'content'      => '<p>Experience certificate body</p>',
            'issued_at'    => now(),
            'issued_by'    => User::create(['name' => 'HR', 'email' => 'hr-letter@test.com', 'password' => 'x'])->id,
        ]);

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.profile'))
            ->assertOk()
            ->assertSee('worker@test.com')
            ->assertSee('1234567890')
            ->assertSee('G-3')
            ->assertSee('Experience Certificate');
    }

    public function test_calculated_payslip_is_visible_on_portal(): void
    {
        $period = PayrollPeriod::create([
            'factory_id' => $this->factory->id,
            'year'       => now()->year,
            'month'      => now()->month,
            'start_date' => now()->startOfMonth(),
            'end_date'   => now()->endOfMonth(),
            'status'     => 'calculated',
        ]);

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'factory_id'        => $this->factory->id,
            'employee_id'       => $this->employee->id,
            'pay_type'          => 'salary',
            'gross_pay'         => 30000,
            'net_pay'           => 29000,
            'present_days'      => 26,
        ]);

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.payslips.show', $item))
            ->assertOk()
            ->assertSee('Provisional');

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.payslips.print', ['payslip' => $item, 'download' => 1]))
            ->assertOk();
    }

    public function test_leave_history_shows_approval_steps(): void
    {
        $leaveType = LeaveType::create(['name' => 'Casual', 'is_paid' => true, 'is_active' => true]);
        $application = LeaveApplication::create([
            'factory_id'             => $this->factory->id,
            'employee_id'            => $this->employee->id,
            'leave_type_id'          => $leaveType->id,
            'start_date'             => now()->addDays(3),
            'end_date'               => now()->addDays(3),
            'total_days'             => 1,
            'reason'                 => 'Personal',
            'status'                 => 'pending',
            'current_approval_step'  => 2,
            'applied_at'             => now(),
        ]);

        LeaveApproval::create([
            'leave_application_id' => $application->id,
            'step'                 => 1,
            'step_label'           => 'Reporting Person',
            'status'               => 'approved',
            'acted_at'             => now(),
        ]);
        LeaveApproval::create([
            'leave_application_id' => $application->id,
            'step'                 => 2,
            'step_label'           => 'HR',
            'status'               => 'pending',
        ]);

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.leave'))
            ->assertOk()
            ->assertSee('Reporting Person')
            ->assertSee('Awaiting HR');
    }

    public function test_resignation_submit_route_is_removed(): void
    {
        $this->actingAs($this->portalUser, 'employee')
            ->post('/employee/separation', [
                'separation_type'  => 'resigned',
                'application_date' => now()->toDateString(),
                'last_working_day' => now()->addDays(30)->toDateString(),
                'reason'           => 'Test',
            ])
            ->assertRedirect(route('employee.exit'));

        $this->assertSame(0, EmployeeSeparation::count());
    }

    public function test_exit_page_shows_clearance_and_settlement(): void
    {
        $separation = EmployeeSeparation::create([
            'factory_id'            => $this->factory->id,
            'employee_id'           => $this->employee->id,
            'separation_type'       => 'resigned',
            'source'                => 'admin',
            'status'                => 'approved',
            'application_date'      => now(),
            'last_working_day'      => now()->addDays(15),
            'reason'                => 'Admin initiated',
            'exit_clearance'        => ['hr' => true, 'it' => true, 'stores' => false, 'accounts' => false, 'line_chief' => false],
            'applied_at'            => now(),
            'approved_at'           => now(),
        ]);

        FinalSettlement::create([
            'factory_id'       => $this->factory->id,
            'employee_id'      => $this->employee->id,
            'separation_type'  => 'resigned',
            'last_working_day' => $separation->last_working_day,
            'status'           => 'paid',
            'net_payable'      => 45000,
            'unpaid_salary'    => 20000,
            'paid_at'          => now(),
        ]);

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.exit'))
            ->assertOk()
            ->assertSee('Exit Clearance')
            ->assertSee('Cleared')
            ->assertSee('Pending');

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.exit.settlement'))
            ->assertOk()
            ->assertSee('45,000');
    }

    public function test_manager_can_open_team_hub(): void
    {
        $reportee = Employee::create([
            'factory_id'      => $this->factory->id,
            'employee_code'   => 'PF-1002',
            'name'            => 'Reportee',
            'reporting_to_id' => $this->employee->id,
            'status'          => 'active',
        ]);

        $leaveType = LeaveType::create(['name' => 'Casual', 'is_paid' => true, 'is_active' => true]);
        LeaveApplication::create([
            'factory_id'            => $this->factory->id,
            'employee_id'           => $reportee->id,
            'leave_type_id'         => $leaveType->id,
            'start_date'            => now()->addDays(2),
            'end_date'              => now()->addDays(2),
            'total_days'            => 1,
            'reason'                => 'Need leave',
            'status'                => 'pending',
            'current_approval_step' => 1,
            'applied_at'            => now(),
        ]);

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.team'))
            ->assertOk()
            ->assertSee('Reportee');
    }

    public function test_promotion_and_increment_detail_pages(): void
    {
        $promotion = EmployeePromotion::create([
            'factory_id'           => $this->factory->id,
            'employee_id'          => $this->employee->id,
            'movement_type'        => 'promotion',
            'status'               => 'approved',
            'to_designation_id'    => $this->employee->designation_id,
            'from_gross_salary'    => 30000,
            'to_gross_salary'      => 35000,
            'effective_date'       => now()->subMonth(),
            'approved_at'          => now(),
        ]);

        $increment = SalaryIncrementLog::create([
            'factory_id'      => $this->factory->id,
            'employee_id'     => $this->employee->id,
            'previous_gross'  => 30000,
            'new_gross'       => 32000,
            'applied_at'      => now(),
        ]);

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.career.promotions.show', $promotion))
            ->assertOk()
            ->assertSee('35,000');

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.career.increments.show', $increment))
            ->assertOk()
            ->assertSee('32,000');
    }

    public function test_contract_renewal_admin_flow_updates_employee_end_date(): void
    {
        $admin = User::create([
            'name'     => 'HR',
            'email'    => 'hr@test.com',
            'password' => 'password',
            'role_id'  => Role::create([
                'name'        => 'HR Manage',
                'permissions' => ['hrm.employees.view', 'hrm.employees.manage'],
            ])->id,
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.employees.contract-renewals.store', $this->employee), [
            'new_end_date' => now()->addYear()->toDateString(),
            'notes'        => 'Annual renewal',
        ])->assertRedirect();

        $renewal = ContractRenewal::first();
        $this->assertSame('pending', $renewal->status);

        $this->actingAs($admin)->post(route('admin.hrm.contract-renewals.approve', $renewal))
            ->assertRedirect();

        $this->employee->refresh();
        $this->assertTrue($this->employee->contract_end_date->isSameDay($renewal->fresh()->new_end_date));

        $this->actingAs($this->portalUser, 'employee')
            ->get(route('employee.exit.contracts'))
            ->assertOk()
            ->assertSee('Approved');
    }
}
