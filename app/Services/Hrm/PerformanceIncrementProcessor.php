<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\PerformanceIncrementItem;
use App\Models\Hrm\PerformanceIncrementRun;
use App\Models\Hrm\PerformanceReview;
use App\Models\Hrm\SalaryIncrementLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformanceIncrementProcessor
{
    public function __construct(
        private PerformanceIncrementBandService $bands,
        private SalaryIncrementService $salaryIncrements,
    ) {}

    public function calculate(PerformanceIncrementRun $run, User $user): PerformanceIncrementRun
    {
        if ($run->isApplied()) {
            throw ValidationException::withMessages([
                'status' => 'Applied increment runs cannot be recalculated.',
            ]);
        }

        return DB::transaction(function () use ($run, $user) {
            $reviews = $this->eligibleReviews($run);

            PerformanceIncrementItem::query()
                ->where('performance_increment_run_id', $run->id)
                ->delete();

            $minimumScore = (float) config('hrm.performance.minimum_pass_score', 60);

            foreach ($reviews as $review) {
                $employee = $review->employee;
                $score = (float) $review->overall_score;

                if ($score < $minimumScore) {
                    continue;
                }

                if (! $employee->hasPassedProbation()) {
                    continue;
                }

                $structure = $employee->salaryStructure;

                if (! $structure || ! $structure->is_active || ! $structure->salary_grade_id) {
                    continue;
                }

                if ($structure->pay_type !== 'salary' || (float) $structure->gross_salary <= 0) {
                    continue;
                }

                $band = $this->bands->matchBand($score, $run->factory_id);

                if (! $band || (float) $band->increment_percent <= 0) {
                    continue;
                }

                $previousGross = (float) $structure->gross_salary;
                $incrementPercent = (float) $band->increment_percent;
                $newGross = round($previousGross * (1 + ($incrementPercent / 100)), 2);

                if ($newGross <= $previousGross) {
                    continue;
                }

                PerformanceIncrementItem::create([
                    'performance_increment_run_id' => $run->id,
                    'employee_id'                  => $employee->id,
                    'performance_review_id'        => $review->id,
                    'overall_score'                => $score,
                    'band_name'                    => $band->name,
                    'increment_percent'            => $incrementPercent,
                    'previous_gross'               => $previousGross,
                    'suggested_new_gross'          => $newGross,
                    'final_new_gross'              => $newGross,
                    'increment_amount'             => round($newGross - $previousGross, 2),
                    'status'                       => 'pending',
                ]);
            }

            $run->update([
                'status'        => 'calculated',
                'calculated_at' => now(),
                'calculated_by' => $user->id,
            ]);

            return $run->fresh(['items.employee', 'items.review']);
        });
    }

    public function updateItemOverride(
        PerformanceIncrementItem $item,
        ?float $overrideNewGross = null,
        ?float $overrideIncrementPercent = null,
        ?string $notes = null,
    ): PerformanceIncrementItem {
        $run = $item->run;

        if ($run->isApplied()) {
            throw ValidationException::withMessages([
                'override_new_gross' => 'Cannot override amounts on applied increment runs.',
            ]);
        }

        if ($item->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending items can be overridden.',
            ]);
        }

        $finalNewGross = $overrideNewGross;

        if ($finalNewGross === null && $overrideIncrementPercent !== null) {
            $finalNewGross = round((float) $item->previous_gross * (1 + ($overrideIncrementPercent / 100)), 2);
        }

        if ($finalNewGross === null) {
            $finalNewGross = (float) $item->final_new_gross;
        }

        $item->update([
            'override_new_gross'         => $overrideNewGross,
            'override_increment_percent' => $overrideIncrementPercent,
            'final_new_gross'            => $finalNewGross,
            'increment_amount'           => round($finalNewGross - (float) $item->previous_gross, 2),
            'notes'                      => $notes,
        ]);

        return $item->fresh();
    }

    /** @return array{applied: int, skipped: int, failed: int, errors: list<string>} */
    public function apply(PerformanceIncrementRun $run, User $user): array
    {
        if ($run->status !== 'calculated') {
            throw ValidationException::withMessages([
                'status' => 'Only calculated increment runs can be applied to salary.',
            ]);
        }

        $applied = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        DB::transaction(function () use ($run, $user, &$applied, &$skipped, &$failed, &$errors) {
            $items = $run->items()->with(['employee.salaryStructure', 'review'])->where('status', 'pending')->get();

            foreach ($items as $item) {
                $newGross = $item->resolvedNewGross();

                if ($newGross <= (float) $item->previous_gross) {
                    $item->update(['status' => 'skipped', 'error_message' => 'No increment amount.']);
                    $skipped++;

                    continue;
                }

                try {
                    $employee = $item->employee;
                    $this->salaryIncrements->applyDirectGross(
                        $employee,
                        $newGross,
                        $user,
                        $item->performance_review_id,
                        $run->id,
                    );

                    $logId = SalaryIncrementLog::query()
                        ->where('employee_id', $employee->id)
                        ->where('performance_increment_run_id', $run->id)
                        ->latest('id')
                        ->value('id');

                    $item->update([
                        'status'                  => 'applied',
                        'final_new_gross'         => $newGross,
                        'increment_amount'        => round($newGross - (float) $item->previous_gross, 2),
                        'salary_increment_log_id' => $logId,
                        'error_message'           => null,
                    ]);

                    $applied++;
                } catch (\Throwable $e) {
                    $item->update([
                        'status'        => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                    $errors[] = "{$item->employee?->employee_code}: {$e->getMessage()}";
                    $failed++;
                }
            }

            $run->update([
                'status'     => 'applied',
                'applied_by' => $user->id,
                'applied_at' => now(),
            ]);
        });

        return compact('applied', 'skipped', 'failed', 'errors');
    }

    /** @return \Illuminate\Support\Collection<int, PerformanceReview> */
    private function eligibleReviews(PerformanceIncrementRun $run): \Illuminate\Support\Collection
    {
        $query = PerformanceReview::query()
            ->with(['employee.salaryStructure'])
            ->where('factory_id', $run->factory_id)
            ->where('cycle_type', 'annual_12m')
            ->where('status', 'approved')
            ->whereNotNull('overall_score');

        if ($run->performance_cycle_id) {
            $query->where('cycle_id', $run->performance_cycle_id);
        } else {
            $query->whereHas('cycle', fn ($q) => $q->where('year', $run->year));
        }

        return $query->get()->filter(function (PerformanceReview $review) use ($run) {
            if ($this->alreadyAppliedForReview($review, $run)) {
                return false;
            }

            return true;
        });
    }

    private function alreadyAppliedForReview(PerformanceReview $review, PerformanceIncrementRun $run): bool
    {
        return PerformanceIncrementItem::query()
            ->where('performance_review_id', $review->id)
            ->where('status', 'applied')
            ->where('performance_increment_run_id', '!=', $run->id)
            ->exists();
    }
}
