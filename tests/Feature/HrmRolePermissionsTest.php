<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\EmployeeSeparation;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\PerformanceCycle;
use App\Models\Hrm\PerformanceReview;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PortalSeparationStatusNotification;
use App\Notifications\SeparationRejectedNotification;
use App\Services\Hrm\HrmNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class HrmRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_rmg_view_does_not_fallback_to_hrm_masters(): void
    {
        $role = Role::create([
            'name'        => 'Masters Only',
            'permissions' => ['hrm.masters.view'],
        ]);

        $this->assertTrue($role->hasPermission('hrm.hrm-buildings.view'));
        $this->assertFalse($role->hasPermission('hrm.rmg.view'));
        $this->assertFalse($role->hasPermission('hrm.employees.view'));
    }

    public function test_finance_submodule_falls_back_to_parent_permission(): void
    {
        $role = Role::create([
            'name'        => 'Finance Viewer',
            'permissions' => ['hrm.finance.view'],
        ]);

        $this->assertTrue($role->hasPermission('hrm.finance.loans.view'));
        $this->assertFalse($role->hasPermission('hrm.finance.loans.manage'));
    }

    public function test_promotion_permissions_granted_to_administrator(): void
    {
        $admin = Role::where('name', 'Administrator')->firstOrFail();

        $this->assertTrue($admin->hasPermission('hrm.employees.promotion.view'));
        $this->assertTrue($admin->hasPermission('hrm.employees.promotion.manage'));
        $this->assertTrue($admin->hasPermission('hrm.employees.promotion.approve'));
        $this->assertTrue($admin->hasPermission('hrm.rmg.view'));
    }

    public function test_hr_manager_role_exists_with_hr_permissions(): void
    {
        $hrManager = Role::where('name', 'HR Manager')->firstOrFail();

        $this->assertTrue($hrManager->hasPermission('hrm.employees.manage'));
        $this->assertTrue($hrManager->hasPermission('hrm.employees.promotion.approve'));
        $this->assertTrue($hrManager->hasPermission('hrm.performance.approve'));
    }

    public function test_performance_rate_route_requires_rate_permission(): void
    {
        $factory = Factory::create(['name' => 'Perf Factory', 'is_active' => true]);

        $viewerRole = Role::create([
            'name'        => 'Perf Viewer',
            'permissions' => ['hrm.performance.view'],
        ]);

        $viewer = User::create([
            'name'     => 'Perf Viewer',
            'email'    => 'perf-viewer@test.com',
            'password' => 'password',
            'role_id'  => $viewerRole->id,
        ]);

        $review = $this->createPerformanceReview($factory);

        $this->actingAs($viewer)
            ->post(route('admin.hrm.performance.reviews.rate', $review), [
                'ratings' => [],
            ])
            ->assertForbidden();
    }

    public function test_separation_rejected_notifies_hr_admin(): void
    {
        NotificationFacade::fake();

        AppSetting::current()->update(['notify_popup_enabled' => true]);
        AppSetting::clearCache();

        $factory = Factory::create(['name' => 'Sep Factory', 'is_active' => true]);

        $hrRole = Role::create([
            'name'        => 'Separation HR',
            'permissions' => ['hrm.employees.separation.view'],
        ]);

        $hrUser = User::create([
            'name'     => 'Sep HR',
            'email'    => 'sep-hr@test.com',
            'password' => 'password',
            'role_id'  => $hrRole->id,
        ]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'SEP-1',
            'name'          => 'Leaving Worker',
            'status'        => 'active',
        ]);

        EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => 'password',
            'is_active'   => true,
        ]);

        $separation = EmployeeSeparation::create([
            'factory_id'       => $factory->id,
            'employee_id'      => $employee->id,
            'separation_type'  => 'resigned',
            'status'           => 'rejected',
            'application_date' => now()->toDateString(),
            'last_working_day' => now()->toDateString(),
        ]);

        app(HrmNotificationService::class)->separationRejected($separation);

        NotificationFacade::assertSentTo($hrUser, SeparationRejectedNotification::class);
        NotificationFacade::assertSentTo($employee->portalUser, PortalSeparationStatusNotification::class);
    }

    public function test_payslip_notification_respects_popup_toggle(): void
    {
        NotificationFacade::fake();

        AppSetting::current()->update(['notify_popup_enabled' => false]);
        AppSetting::clearCache();

        $factory = Factory::create(['name' => 'Pay Factory', 'is_active' => true]);
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'PAY-1',
            'name'          => 'Pay Worker',
            'status'        => 'active',
        ]);

        EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => 'password',
            'is_active'   => true,
        ]);

        $period = PayrollPeriod::create([
            'factory_id' => $factory->id,
            'year'       => 2026,
            'month'      => 6,
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'closed',
        ]);

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id'       => $employee->id,
            'factory_id'        => $factory->id,
            'pay_type'          => 'salary',
            'gross_pay'         => 10000,
            'net_pay'           => 9000,
        ]);

        app(HrmNotificationService::class)->payslipReady($item);

        NotificationFacade::assertNothingSent();
    }

    private function createPerformanceReview(Factory $factory): PerformanceReview
    {
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'PR-1',
            'name'          => 'Review Worker',
            'status'        => 'active',
        ]);

        $template = app(\App\Services\Hrm\PerformanceTemplateService::class)->ensureDefaultTemplate();

        $cycle = PerformanceCycle::create([
            'factory_id'  => $factory->id,
            'cycle_type'  => 'probation_6m',
            'name'        => 'Test Cycle',
            'period_from' => now()->subMonths(6)->toDateString(),
            'period_to'   => now()->toDateString(),
            'status'      => 'open',
            'template_id' => $template->id,
        ]);

        return PerformanceReview::create([
            'factory_id'  => $factory->id,
            'cycle_id'    => $cycle->id,
            'employee_id' => $employee->id,
            'template_id' => $template->id,
            'cycle_type'  => 'probation_6m',
            'status'      => 'pending_rating',
            'period_from' => now()->subMonths(6)->toDateString(),
            'period_to'   => now()->toDateString(),
        ]);
    }
}
