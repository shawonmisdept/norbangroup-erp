<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Hrm\PerformanceBonusRun;
use App\Models\Hrm\PerformanceCycle;
use App\Models\Hrm\PerformanceIncrementRun;
use App\Models\Hrm\PerformanceReview;
use App\Models\Hrm\PerformanceScore;
use App\Models\Hrm\PerformanceTemplate;
use App\Models\User;
use App\Services\Hrm\PerformanceBonusCalculator;
use App\Services\Hrm\PerformanceIncrementProcessor;
use App\Services\Hrm\PerformanceScoreCalculator;
use App\Services\Hrm\PerformanceTemplateService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoPerformanceSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('seeders/data/demo_performance.php');

        $factory = Factory::where('name', $data['factory'])->where('is_active', true)->first();

        if (! $factory) {
            $this->command?->warn("Factory \"{$data['factory']}\" not found. Run db:seed first.");

            return;
        }

        $admin = User::query()->whereHas('role', fn ($q) => $q->where('name', 'Administrator'))->first()
            ?? User::query()->first();

        if (! $admin) {
            $this->command?->warn('No admin user found.');

            return;
        }

        app(PerformanceSeeder::class)->run();

        $template = app(PerformanceTemplateService::class)->ensureDefaultTemplate();

        $this->resetDemoData($factory->id, $data['prefix']);

        $this->seedProbationFlags($factory->id);

        $cycles = $this->seedCycles($factory->id, $template->id, $admin->id, $data);

        $reviewCount = $this->seedReviews($factory->id, $template, $cycles, $data['reviews'], $admin);

        $this->ensurePortalAccess($factory->id, $data['portal_codes'] ?? []);

        $bonusRun = $this->seedBonusRun($factory->id, $cycles['mid_year'], $data['year'], $admin);
        $incrementRun = $this->seedIncrementRun($factory->id, $cycles['annual'], $data['year'], $admin);

        $this->printSummary($cycles, $reviewCount, $bonusRun, $incrementRun);
    }

    private function resetDemoData(int $factoryId, string $prefix): void
    {
        $cycleIds = PerformanceCycle::query()
            ->where('factory_id', $factoryId)
            ->where('name', 'like', $prefix . '%')
            ->pluck('id');

        if ($cycleIds->isNotEmpty()) {
            PerformanceBonusRun::query()
                ->where('factory_id', $factoryId)
                ->whereIn('performance_cycle_id', $cycleIds)
                ->delete();

            PerformanceIncrementRun::query()
                ->where('factory_id', $factoryId)
                ->whereIn('performance_cycle_id', $cycleIds)
                ->delete();

            PerformanceCycle::query()->whereIn('id', $cycleIds)->delete();
        }

        PerformanceBonusRun::query()
            ->where('factory_id', $factoryId)
            ->where('name', 'like', $prefix . '%')
            ->delete();

        PerformanceIncrementRun::query()
            ->where('factory_id', $factoryId)
            ->where('name', 'like', $prefix . '%')
            ->delete();
    }

    private function seedProbationFlags(int $factoryId): void
    {
        Employee::query()
            ->where('factory_id', $factoryId)
            ->where('status', 'active')
            ->whereNull('probation_passed_at')
            ->update(['probation_passed_at' => now()->subYear()]);

        Employee::query()
            ->where('factory_id', $factoryId)
            ->where('status', 'probation')
            ->update(['probation_passed_at' => null]);
    }

    /** @return array<string, PerformanceCycle> */
    private function seedCycles(int $factoryId, int $templateId, int $adminId, array $data): array
    {
        $cycles = [];

        foreach ($data['cycles'] as $key => $row) {
            $cycles[$key] = PerformanceCycle::create([
                'factory_id'  => $factoryId,
                'cycle_type'  => $row['cycle_type'],
                'name'        => $row['name'],
                'year'        => $data['year'],
                'period_from' => $row['period_from'],
                'period_to'   => $row['period_to'],
                'status'      => $row['status'],
                'template_id' => $templateId,
                'opened_by'   => $adminId,
                'opened_at'   => now()->subDays(14),
                'closed_at'   => ($row['status'] ?? '') === 'closed' ? now()->subDays(3) : null,
            ]);
        }

        return $cycles;
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function seedReviews(
        int $factoryId,
        PerformanceTemplate $template,
        array $cycles,
        array $rows,
        User $admin,
    ): int {
        $calculator = app(PerformanceScoreCalculator::class);
        $count = 0;

        foreach ($rows as $row) {
            $employee = Employee::query()
                ->where('factory_id', $factoryId)
                ->where('employee_code', $row['code'])
                ->first();

            if (! $employee) {
                $this->command?->warn("  Skipped review — employee {$row['code']} not found.");

                continue;
            }

            /** @var PerformanceCycle $cycle */
            $cycle = $cycles[$row['cycle']] ?? null;

            if (! $cycle) {
                continue;
            }

            $periodFrom = $cycle->cycle_type === 'probation_6m' && $employee->joining_date
                ? Carbon::parse($employee->joining_date)
                : Carbon::parse($cycle->period_from);

            $periodTo = $cycle->cycle_type === 'probation_6m' && $employee->joining_date
                ? Carbon::parse($employee->joining_date)->addMonths(6)->subDay()->min(Carbon::parse($cycle->period_to))
                : Carbon::parse($cycle->period_to);

            $review = PerformanceReview::create([
                'factory_id'      => $factoryId,
                'cycle_id'        => $cycle->id,
                'employee_id'     => $employee->id,
                'template_id'     => $template->id,
                'cycle_type'      => $cycle->cycle_type,
                'status'          => $row['status'],
                'period_from'     => $periodFrom->toDateString(),
                'period_to'       => $periodTo->toDateString(),
                'reporting_to_id' => $employee->reporting_to_id,
                'auto_metrics'    => [
                    'working_days' => 22,
                    'present_days' => 20,
                    'late_days'    => 2,
                    'leave_days'   => 1,
                ],
                'manual_fallback' => false,
                'created_by'      => $admin->id,
                'rated_by_user_id'=> in_array($row['status'], ['pending_hr', 'approved'], true) ? $admin->id : null,
                'rated_at'        => in_array($row['status'], ['pending_hr', 'approved'], true) ? now()->subDays(5) : null,
                'hr_approved_by'  => $row['status'] === 'approved' ? $admin->id : null,
                'hr_approved_at'  => $row['status'] === 'approved' ? now()->subDays(2) : null,
            ]);

            foreach ($template->criteria as $criterion) {
                $scoreValue = $row['scores'][$criterion->code] ?? null;
                $isAuto = $criterion->criterion_type === 'auto';

                PerformanceScore::create([
                    'review_id'      => $review->id,
                    'criterion_id'   => $criterion->id,
                    'code'           => $criterion->code,
                    'label'          => $criterion->label,
                    'criterion_type' => $criterion->criterion_type,
                    'weight'         => $criterion->weight,
                    'score'          => $scoreValue,
                    'is_auto'        => $isAuto && $scoreValue !== null,
                    'auto_source'    => $isAuto && $scoreValue !== null ? $review->auto_metrics : null,
                ]);
            }

            $review->load('scores');

            if ($review->status !== 'pending_rating') {
                $review->update(['overall_score' => $calculator->calculateOverall($review)]);
            }

            if ($cycle->cycle_type === 'probation_6m' && $review->isApproved() && $review->passedMinimumScore()) {
                $employee->update([
                    'probation_passed_at' => now(),
                    'status'              => $employee->status === 'probation' ? 'active' : $employee->status,
                ]);
            }

            $count++;
        }

        foreach ($cycles as $cycle) {
            $cycle->update([
                'review_count' => PerformanceReview::query()->where('cycle_id', $cycle->id)->count(),
            ]);
        }

        return $count;
    }

    /** @param list<string> $codes */
    private function ensurePortalAccess(int $factoryId, array $codes): void
    {
        $employees = Employee::query()
            ->where('factory_id', $factoryId)
            ->whereIn('employee_code', $codes)
            ->get();

        foreach ($employees as $employee) {
            if (! $employee->email) {
                $employee->update([
                    'email' => strtolower($employee->employee_code) . '@demo.norbangroup.local',
                ]);
            }

            EmployeePortalUser::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'password'  => Hash::make('password'),
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedBonusRun(int $factoryId, PerformanceCycle $cycle, int $year, User $admin): ?PerformanceBonusRun
    {
        $run = PerformanceBonusRun::create([
            'factory_id'           => $factoryId,
            'performance_cycle_id' => $cycle->id,
            'year'                 => $year,
            'name'                 => '[Demo] Mid-Year Bonus Run 2026',
            'bonus_base'           => config('hrm.performance.bonus_base_default', 'gross'),
            'status'               => 'draft',
            'created_by'           => $admin->id,
        ]);

        $calculator = app(PerformanceBonusCalculator::class);

        try {
            $calculator->calculate($run, $admin);
            $calculator->approve($run->fresh(), $admin);
        } catch (\Throwable $e) {
            $this->command?->warn('  Bonus run skipped: ' . $e->getMessage());

            return null;
        }

        return $run->fresh(['items.employee']);
    }

    private function seedIncrementRun(int $factoryId, PerformanceCycle $cycle, int $year, User $admin): ?PerformanceIncrementRun
    {
        $run = PerformanceIncrementRun::create([
            'factory_id'           => $factoryId,
            'performance_cycle_id' => $cycle->id,
            'year'                 => $year,
            'name'                 => '[Demo] Annual Increment Run 2026',
            'status'               => 'draft',
            'created_by'           => $admin->id,
        ]);

        $processor = app(PerformanceIncrementProcessor::class);

        try {
            $processor->calculate($run, $admin);
        } catch (\Throwable $e) {
            $this->command?->warn('  Increment run skipped: ' . $e->getMessage());

            return null;
        }

        return $run->fresh(['items.employee']);
    }

    /** @param array<string, PerformanceCycle> $cycles */
    private function printSummary(
        array $cycles,
        int $reviewCount,
        ?PerformanceBonusRun $bonusRun,
        ?PerformanceIncrementRun $incrementRun,
    ): void {
        $this->command?->info("Demo performance data seeded ({$reviewCount} reviews):");

        foreach ($cycles as $key => $cycle) {
            $pendingRating = $cycle->reviews()->where('status', 'pending_rating')->count();
            $pendingHr = $cycle->reviews()->where('status', 'pending_hr')->count();
            $approved = $cycle->reviews()->where('status', 'approved')->count();

            $this->command?->info("  {$cycle->name}: {$approved} approved, {$pendingHr} pending HR, {$pendingRating} pending rating");
        }

        if ($bonusRun) {
            $this->command?->info("  Bonus run: {$bonusRun->items->count()} employee(s), total ৳" . number_format($bonusRun->totalBonus(), 0) . " ({$bonusRun->status})");
        }

        if ($incrementRun) {
            $this->command?->info("  Increment run: {$incrementRun->items->count()} employee(s) calculated ({$incrementRun->status})");
        }

        $this->command?->info('Admin: /admin/hrm/performance');
        $this->command?->info('Employee portal: NCL-D002 / password → Performance (approved mid-year score)');
        $this->command?->info('Employee portal: NCL-D008 / password → probation review pending rating');
    }
}
