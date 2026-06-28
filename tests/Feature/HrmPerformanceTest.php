<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeeServiceHistory;
use App\Models\Hrm\PerformanceBonusItem;
use App\Models\Hrm\PerformanceBonusRun;
use App\Models\Hrm\PerformanceIncrementItem;
use App\Models\Hrm\PerformanceIncrementRun;
use App\Models\Hrm\PerformanceCycle;
use App\Models\Hrm\PerformanceReview;
use App\Models\Hrm\PerformanceTemplate;
use App\Models\Hrm\PerformanceBonusBand;
use App\Models\Hrm\PerformanceIncrementBand;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryGradeDetail;
use App\Models\Hrm\SalaryHead;
use App\Models\Hrm\SalaryIncrementLog;
use App\Models\Hrm\SalaryStructure;
use App\Services\Hrm\SalaryFormulaCalculator;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Hrm\PerformanceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HrmPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function performanceAdmin(): User
    {
        $role = Role::create([
            'name'        => 'HR Performance Admin',
            'permissions' => [
                'hrm.performance.view',
                'hrm.performance.manage',
                'hrm.performance.rate',
                'hrm.performance.approve',
                'hrm.performance.bonus.view',
                'hrm.performance.bonus.manage',
                'hrm.performance.increment.view',
                'hrm.performance.increment.manage',
            ],
        ]);

        return User::create([
            'name'     => 'HR Perf Admin',
            'email'    => 'hr-perf@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    public function test_performance_hub_loads(): void
    {
        $this->actingAs($this->performanceAdmin())
            ->get(route('admin.hrm.performance.hub'))
            ->assertOk();
    }

    public function test_probation_cycle_generates_review_and_dual_approval_flow(): void
    {
        $factory = Factory::create(['name' => 'Perf Factory', 'is_active' => true]);
        $dept = Department::create(['factory_id' => $factory->id, 'name' => 'Sewing', 'is_active' => true]);
        $designation = Designation::create(['name' => 'Operator', 'is_active' => true]);
        $supervisor = Designation::create(['name' => 'Line Chief', 'is_active' => true]);
        $admin = $this->performanceAdmin();

        $manager = Employee::create([
            'factory_id'     => $factory->id,
            'department_id'  => $dept->id,
            'designation_id' => $supervisor->id,
            'employee_code'  => 'MGR-01',
            'name'           => 'Line Manager',
            'status'         => 'active',
            'joining_date'   => now()->subYears(3)->toDateString(),
        ]);

        $joinDate = now()->subMonths(6)->subDays(5);

        $employee = Employee::create([
            'factory_id'      => $factory->id,
            'department_id'   => $dept->id,
            'designation_id'  => $designation->id,
            'reporting_to_id' => $manager->id,
            'employee_code'   => 'PERF-W1',
            'name'            => 'Probation Worker',
            'status'          => 'probation',
            'joining_date'    => $joinDate->toDateString(),
        ]);

        for ($i = 0; $i < 10; $i++) {
            AttendanceDailyLog::create([
                'factory_id'      => $factory->id,
                'employee_id'     => $employee->id,
                'attendance_date' => $joinDate->copy()->addDays($i)->toDateString(),
                'status'          => $i === 3 ? 'late' : 'present',
                'punch_count'     => 2,
            ]);
        }

        AttendanceDailyLog::create([
            'factory_id'      => $factory->id,
            'employee_id'     => $employee->id,
            'attendance_date' => $joinDate->copy()->addDays(11)->toDateString(),
            'status'          => 'leave',
            'punch_count'     => 0,
        ]);

        $periodFrom = $joinDate->toDateString();
        $periodTo = $joinDate->copy()->addMonths(6)->toDateString();

        $this->actingAs($admin)->post(route('admin.hrm.performance.cycles.store'), [
            'factory_id'  => $factory->id,
            'cycle_type'  => 'probation_6m',
            'name'        => 'Probation Batch Test',
            'period_from' => $periodFrom,
            'period_to'   => $periodTo,
        ])->assertRedirect();

        $cycle = PerformanceCycle::first();
        $this->assertSame(1, $cycle->review_count);

        $review = PerformanceReview::first();
        $this->assertSame('pending_rating', $review->status);
        $this->assertFalse($review->manual_fallback);
        $this->assertNotNull($review->auto_metrics);

        $this->actingAs($admin)->post(route('admin.hrm.performance.reviews.rate', $review), [
            'scores' => [
                'work_quality' => 85,
                'behaviour'    => 90,
            ],
            'probation_recommendation' => 'Recommend confirmation',
            'apply_confirmation'       => true,
        ])->assertRedirect();

        $review->refresh();
        $this->assertSame('pending_hr', $review->status);
        $this->assertNotNull($review->overall_score);
        $this->assertGreaterThanOrEqual(60, (float) $review->overall_score);

        $this->actingAs($admin)->post(route('admin.hrm.performance.reviews.approve', $review))
            ->assertRedirect();

        $review->refresh();
        $employee->refresh();

        $this->assertSame('approved', $review->status);
        $this->assertNotNull($employee->probation_passed_at);
        $this->assertSame('active', $employee->status);
        $this->assertNotNull($employee->confirmation_date);

        $this->assertTrue(
            EmployeeServiceHistory::where('employee_id', $employee->id)
                ->where('event_type', 'performance')
                ->exists()
        );
    }

    public function test_suspended_employee_review_is_blocked(): void
    {
        $factory = Factory::create(['name' => 'Block Factory', 'is_active' => true]);
        $dept = Department::create(['factory_id' => $factory->id, 'name' => 'Finishing', 'is_active' => true]);
        $designation = Designation::create(['name' => 'Helper', 'is_active' => true]);
        $admin = $this->performanceAdmin();

        $joinDate = now()->subMonths(7);

        Employee::create([
            'factory_id'      => $factory->id,
            'department_id'   => $dept->id,
            'designation_id'  => $designation->id,
            'employee_code'   => 'SUSP-01',
            'name'            => 'Suspended Worker',
            'status'          => 'suspended',
            'joining_date'    => $joinDate->toDateString(),
            'reporting_to_id' => null,
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.performance.cycles.store'), [
            'factory_id'  => $factory->id,
            'cycle_type'  => 'probation_6m',
            'name'        => 'Blocked Batch',
            'period_from' => $joinDate->toDateString(),
            'period_to'   => now()->toDateString(),
        ]);

        $review = PerformanceReview::first();
        $this->assertSame('blocked', $review->status);
        $this->assertStringContainsString('suspended', strtolower($review->blocked_reason));
    }

    public function test_mid_year_cycle_requires_probation_passed(): void
    {
        $factory = Factory::create(['name' => 'Midyear Factory', 'is_active' => true]);
        $dept = Department::create(['factory_id' => $factory->id, 'name' => 'Cutting', 'is_active' => true]);
        $designation = Designation::create(['name' => 'Cutter', 'is_active' => true]);
        $admin = $this->performanceAdmin();

        Employee::create([
            'factory_id'     => $factory->id,
            'department_id'  => $dept->id,
            'designation_id' => $designation->id,
            'employee_code'  => 'NO-PASS',
            'name'           => 'No Probation Pass',
            'status'         => 'active',
            'joining_date'   => now()->subYear()->toDateString(),
        ]);

        Employee::create([
            'factory_id'          => $factory->id,
            'department_id'       => $dept->id,
            'designation_id'      => $designation->id,
            'employee_code'       => 'PASSED',
            'name'                => 'Probation Passed',
            'status'              => 'active',
            'joining_date'        => now()->subYear()->toDateString(),
            'probation_passed_at' => now()->subMonths(6),
            'reporting_to_id'     => null,
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.performance.cycles.store'), [
            'factory_id'  => $factory->id,
            'cycle_type'  => 'mid_year_6m',
            'name'        => 'Jan Mid-Year 2026',
            'year'        => 2026,
            'period_from' => '2025-07-01',
            'period_to'   => '2025-12-31',
        ]);

        $cycle = PerformanceCycle::first();
        $this->assertSame(1, $cycle->review_count);
        $this->assertSame('PASSED', PerformanceReview::first()->employee->employee_code);
    }

    public function test_performance_bonus_run_calculates_from_approved_mid_year_review(): void
    {
        $factory = Factory::create(['name' => 'Bonus Factory', 'is_active' => true]);
        $dept = Department::create(['factory_id' => $factory->id, 'name' => 'Production', 'is_active' => true]);
        $designation = Designation::create(['name' => 'Operator', 'is_active' => true]);
        $admin = $this->performanceAdmin();

        $employee = Employee::create([
            'factory_id'          => $factory->id,
            'department_id'       => $dept->id,
            'designation_id'      => $designation->id,
            'employee_code'       => 'BONUS-01',
            'name'                => 'Bonus Worker',
            'status'              => 'active',
            'joining_date'        => now()->subYear()->toDateString(),
            'probation_passed_at' => now()->subMonths(8),
        ]);

        SalaryStructure::create([
            'factory_id'   => $factory->id,
            'employee_id'  => $employee->id,
            'gross_salary' => 20000,
            'basic_salary' => 12000,
            'pay_type'     => 'salary',
            'is_active'    => true,
        ]);

        $cycle = PerformanceCycle::create([
            'factory_id'  => $factory->id,
            'cycle_type'  => 'mid_year_6m',
            'name'        => 'Jan Mid-Year 2026',
            'year'        => 2026,
            'period_from' => '2025-07-01',
            'period_to'   => '2025-12-31',
            'status'      => 'open',
            'opened_at'   => now(),
            'review_count'=> 0,
        ]);

        $review = PerformanceReview::create([
            'factory_id'      => $factory->id,
            'cycle_id'        => $cycle->id,
            'employee_id'     => $employee->id,
            'template_id'     => app(\App\Services\Hrm\PerformanceTemplateService::class)->ensureDefaultTemplate()->id,
            'cycle_type'      => 'mid_year_6m',
            'status'          => 'approved',
            'period_from'     => '2025-07-01',
            'period_to'       => '2025-12-31',
            'overall_score'   => 92,
            'hr_approved_at'  => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.performance.bonus-runs.store'), [
            'factory_id'           => $factory->id,
            'performance_cycle_id' => $cycle->id,
            'year'                 => 2026,
            'name'                 => 'Bonus Run 2026',
            'bonus_base'           => 'gross',
        ])->assertRedirect();

        $run = PerformanceBonusRun::first();

        $this->actingAs($admin)->post(route('admin.hrm.performance.bonus-runs.calculate', $run))
            ->assertRedirect();

        $item = PerformanceBonusItem::first();
        $this->assertNotNull($item);
        $this->assertSame($review->id, $item->performance_review_id);
        $this->assertSame('Outstanding', $item->band_name);
        $this->assertSame(20000.0, (float) $item->base_amount);
        $this->assertSame(20000.0, (float) $item->bonus_amount);

        $this->actingAs($admin)->put(route('admin.hrm.performance.bonus-runs.items.update', [$run, $item]), [
            'override_amount' => 18000,
        ])->assertRedirect();

        $item->refresh();
        $this->assertSame(18000.0, (float) $item->final_amount);

        $this->actingAs($admin)->post(route('admin.hrm.performance.bonus-runs.approve', $run))
            ->assertRedirect();

        $run->refresh();
        $this->assertSame('approved', $run->status);

        $this->actingAs($admin)->get(route('admin.hrm.performance.bonus-runs.export', $run))
            ->assertOk();
    }

    public function test_increment_run_applies_salary_from_approved_annual_review(): void
    {
        $factory = Factory::create(['name' => 'Incr Factory', 'is_active' => true]);
        $dept = Department::create(['factory_id' => $factory->id, 'name' => 'Production', 'is_active' => true]);
        $designation = Designation::create(['name' => 'Operator', 'is_active' => true]);
        $admin = $this->performanceAdmin();

        $grossHead = SalaryHead::create(['factory_id' => $factory->id, 'code' => 'GROSS', 'name' => 'Gross', 'head_type' => 'E', 'sort_order' => 1, 'is_active' => true]);
        $basicHead = SalaryHead::create(['factory_id' => $factory->id, 'code' => 'BASIC', 'name' => 'Basic', 'head_type' => 'E', 'sort_order' => 2, 'is_active' => true]);

        $grade = SalaryGrade::create(['factory_id' => $factory->id, 'code' => 'G1', 'name' => 'Grade 1', 'is_active' => true]);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $grossHead->id, 'detail_type' => 'F', 'is_fixed' => false, 'amount' => 0]);
        SalaryGradeDetail::create(['salary_grade_id' => $grade->id, 'salary_head_id' => $basicHead->id, 'detail_type' => 'M', 'is_fixed' => false, 'formula' => '<GROSS>/1.4']);

        $employee = Employee::create([
            'factory_id'          => $factory->id,
            'department_id'       => $dept->id,
            'designation_id'      => $designation->id,
            'employee_code'       => 'INCR-01',
            'name'                => 'Increment Worker',
            'status'              => 'active',
            'joining_date'        => now()->subYears(2)->toDateString(),
            'probation_passed_at' => now()->subYear(),
        ]);

        $amounts = app(SalaryFormulaCalculator::class)->calculate($grade, 20000);
        $structure = SalaryStructure::create([
            'factory_id'      => $factory->id,
            'employee_id'     => $employee->id,
            'salary_grade_id' => $grade->id,
            'pay_type'        => 'salary',
            'is_active'       => true,
        ]);
        $structure->gross_salary = 20000;
        $structure->syncLegacyFromHeads($amounts);
        $structure->save();

        $cycle = PerformanceCycle::create([
            'factory_id'  => $factory->id,
            'cycle_type'  => 'annual_12m',
            'name'        => 'Annual 2026',
            'year'        => 2026,
            'period_from' => '2025-01-01',
            'period_to'   => '2025-12-31',
            'status'      => 'open',
            'opened_at'   => now(),
        ]);

        PerformanceReview::create([
            'factory_id'     => $factory->id,
            'cycle_id'       => $cycle->id,
            'employee_id'    => $employee->id,
            'template_id'    => app(\App\Services\Hrm\PerformanceTemplateService::class)->ensureDefaultTemplate()->id,
            'cycle_type'     => 'annual_12m',
            'status'         => 'approved',
            'period_from'    => '2025-01-01',
            'period_to'      => '2025-12-31',
            'overall_score'  => 92,
            'hr_approved_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.performance.increment-runs.store'), [
            'factory_id'           => $factory->id,
            'performance_cycle_id' => $cycle->id,
            'year'                 => 2026,
            'name'                 => 'Annual Increment 2026',
        ])->assertRedirect();

        $run = PerformanceIncrementRun::first();

        $this->actingAs($admin)->post(route('admin.hrm.performance.increment-runs.calculate', $run))
            ->assertRedirect();

        $item = PerformanceIncrementItem::first();
        $this->assertSame('Outstanding', $item->band_name);
        $this->assertSame(10.0, (float) $item->increment_percent);
        $this->assertSame(22000.0, (float) $item->final_new_gross);

        $this->actingAs($admin)->post(route('admin.hrm.performance.increment-runs.apply', $run))
            ->assertRedirect();

        $run->refresh();
        $employee->refresh();
        $structure->refresh();

        $this->assertSame('applied', $run->status);
        $this->assertSame(22000.0, (float) $structure->gross_salary);
        $this->assertDatabaseHas('hrm_salary_increment_logs', [
            'employee_id'                  => $employee->id,
            'performance_increment_run_id' => $run->id,
            'previous_gross'               => 20000,
            'new_gross'                    => 22000,
        ]);
        $this->assertSame(1, SalaryIncrementLog::count());
    }

    public function test_performance_seeder_seeds_default_master_data(): void
    {
        $this->seed(PerformanceSeeder::class);

        $this->assertDatabaseHas('hrm_performance_templates', [
            'is_default' => true,
            'name'       => 'Standard Hybrid Template',
        ]);
        $this->assertGreaterThan(0, PerformanceBonusBand::query()->whereNull('factory_id')->count());
        $this->assertGreaterThan(0, PerformanceIncrementBand::query()->whereNull('factory_id')->count());
        $this->assertGreaterThanOrEqual(5, PerformanceTemplate::first()?->criteria()->count() ?? 0);
    }

    public function test_employee_portal_shows_approved_performance_review(): void
    {
        $factory = Factory::create(['name' => 'Portal Factory', 'is_active' => true]);
        $dept = Department::create(['factory_id' => $factory->id, 'name' => 'Sewing', 'is_active' => true]);
        $designation = Designation::create(['name' => 'Operator', 'is_active' => true]);

        $employee = Employee::create([
            'factory_id'     => $factory->id,
            'department_id'  => $dept->id,
            'designation_id' => $designation->id,
            'employee_code'  => 'PORT-01',
            'name'           => 'Portal Worker',
            'status'         => 'active',
            'joining_date'   => now()->subYear()->toDateString(),
        ]);

        $portalUser = EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => 'password',
            'is_active'   => true,
        ]);

        $template = app(\App\Services\Hrm\PerformanceTemplateService::class)->ensureDefaultTemplate();

        $cycle = PerformanceCycle::create([
            'factory_id'  => $factory->id,
            'cycle_type'  => 'annual_12m',
            'name'        => 'Annual 2026',
            'period_from' => now()->subYear()->toDateString(),
            'period_to'   => now()->toDateString(),
            'status'      => 'closed',
            'template_id' => $template->id,
        ]);

        $review = PerformanceReview::create([
            'factory_id'    => $factory->id,
            'cycle_id'      => $cycle->id,
            'employee_id'   => $employee->id,
            'template_id'   => $template->id,
            'cycle_type'    => 'annual_12m',
            'status'        => 'approved',
            'period_from'   => now()->subYear()->toDateString(),
            'period_to'     => now()->toDateString(),
            'overall_score' => 82.5,
            'hr_approved_at'=> now(),
        ]);

        $this->actingAs($portalUser, 'employee')
            ->get(route('employee.performance'))
            ->assertOk()
            ->assertSee('82.5%')
            ->assertSee('Annual Increment');

        $this->actingAs($portalUser, 'employee')
            ->get(route('employee.performance.show', $review))
            ->assertOk()
            ->assertSee('Score Breakdown');
    }

    public function test_employee_cannot_view_other_employees_performance_review(): void
    {
        $factory = Factory::create(['name' => 'Portal Factory 2', 'is_active' => true]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'PORT-A',
            'name'          => 'Worker A',
            'status'        => 'active',
        ]);

        $other = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'PORT-B',
            'name'          => 'Worker B',
            'status'        => 'active',
        ]);

        $portalUser = EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => 'password',
            'is_active'   => true,
        ]);

        $template = app(\App\Services\Hrm\PerformanceTemplateService::class)->ensureDefaultTemplate();

        $cycle = PerformanceCycle::create([
            'factory_id'  => $factory->id,
            'cycle_type'  => 'annual_12m',
            'name'        => 'Annual 2026',
            'period_from' => now()->subYear()->toDateString(),
            'period_to'   => now()->toDateString(),
            'status'      => 'closed',
            'template_id' => $template->id,
        ]);

        $review = PerformanceReview::create([
            'factory_id'  => $factory->id,
            'cycle_id'    => $cycle->id,
            'employee_id' => $other->id,
            'template_id' => $template->id,
            'cycle_type'  => 'annual_12m',
            'status'      => 'approved',
            'period_from' => now()->subYear()->toDateString(),
            'period_to'   => now()->toDateString(),
            'overall_score' => 70,
        ]);

        $this->actingAs($portalUser, 'employee')
            ->get(route('employee.performance.show', $review))
            ->assertForbidden();
    }

    public function test_performance_approval_notifies_employee_portal(): void
    {
        Notification::fake();

        $factory = Factory::create(['name' => 'Notify Perf Factory', 'is_active' => true]);
        $dept = Department::create(['factory_id' => $factory->id, 'name' => 'Sewing', 'is_active' => true]);
        $designation = Designation::create(['name' => 'Operator', 'is_active' => true]);
        $admin = $this->performanceAdmin();

        $employee = Employee::create([
            'factory_id'     => $factory->id,
            'department_id'  => $dept->id,
            'designation_id' => $designation->id,
            'employee_code'  => 'NOTIF-01',
            'name'           => 'Notify Worker',
            'status'         => 'active',
            'joining_date'   => now()->subMonths(6)->toDateString(),
        ]);

        EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => 'password',
            'is_active'   => true,
        ]);

        $template = app(\App\Services\Hrm\PerformanceTemplateService::class)->ensureDefaultTemplate();

        $cycle = PerformanceCycle::create([
            'factory_id'  => $factory->id,
            'cycle_type'  => 'annual_12m',
            'name'        => 'Annual 2026',
            'period_from' => now()->subYear()->toDateString(),
            'period_to'   => now()->toDateString(),
            'status'      => 'closed',
            'template_id' => $template->id,
        ]);

        $review = PerformanceReview::create([
            'factory_id'    => $factory->id,
            'cycle_id'      => $cycle->id,
            'employee_id'   => $employee->id,
            'template_id'   => $template->id,
            'cycle_type'    => 'probation_6m',
            'status'        => 'pending_hr',
            'period_from'   => now()->subMonths(6)->toDateString(),
            'period_to'     => now()->toDateString(),
            'overall_score' => 75,
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.performance.reviews.approve', $review))
            ->assertRedirect();

        Notification::assertSentTo(
            $employee->fresh()->portalUser,
            \App\Notifications\PortalPerformanceReviewApprovedNotification::class
        );
    }

    public function test_hrm_dashboard_shows_performance_stats(): void
    {
        $factory = Factory::create(['name' => 'Dash Perf Factory', 'is_active' => true]);
        $admin = $this->performanceAdmin();
        $template = app(\App\Services\Hrm\PerformanceTemplateService::class)->ensureDefaultTemplate();

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'DASH-01',
            'name'          => 'Dash Worker',
            'status'        => 'active',
        ]);

        $openCycle = PerformanceCycle::create([
            'factory_id'  => $factory->id,
            'cycle_type'  => 'mid_year_6m',
            'name'        => 'Mid Year Open',
            'period_from' => now()->subMonths(6)->toDateString(),
            'period_to'   => now()->toDateString(),
            'status'      => 'open',
            'template_id' => $template->id,
        ]);

        $closedCycle = PerformanceCycle::create([
            'factory_id'  => $factory->id,
            'cycle_type'  => 'annual_12m',
            'name'        => 'Annual Closed',
            'period_from' => now()->subYear()->toDateString(),
            'period_to'   => now()->toDateString(),
            'status'      => 'closed',
            'template_id' => $template->id,
        ]);

        PerformanceReview::create([
            'factory_id'  => $factory->id,
            'cycle_id'    => $openCycle->id,
            'employee_id' => $employee->id,
            'template_id' => $template->id,
            'cycle_type'  => 'mid_year_6m',
            'status'      => 'pending_rating',
            'period_from' => now()->subMonths(6)->toDateString(),
            'period_to'   => now()->toDateString(),
        ]);

        PerformanceReview::create([
            'factory_id'  => $factory->id,
            'cycle_id'    => $closedCycle->id,
            'employee_id' => $employee->id,
            'template_id' => $template->id,
            'cycle_type'  => 'annual_12m',
            'status'      => 'pending_hr',
            'period_from' => now()->subYear()->toDateString(),
            'period_to'   => now()->toDateString(),
            'overall_score' => 80,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.hrm.dashboard'))
            ->assertOk()
            ->assertSee('Performance Overview')
            ->assertSee('Pending Rating')
            ->assertSee('Pending HR');
    }
}
