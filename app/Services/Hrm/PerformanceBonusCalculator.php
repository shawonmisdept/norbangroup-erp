<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\PerformanceBonusItem;
use App\Models\Hrm\PerformanceBonusRun;
use App\Models\Hrm\PerformanceReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformanceBonusCalculator
{
    public function __construct(private PerformanceBonusBandService $bands) {}

    public function calculate(PerformanceBonusRun $run, User $user): PerformanceBonusRun
    {
        if ($run->isApproved()) {
            throw ValidationException::withMessages([
                'status' => 'Approved bonus runs cannot be recalculated.',
            ]);
        }

        return DB::transaction(function () use ($run, $user) {
            $reviews = $this->eligibleReviews($run);

            PerformanceBonusItem::query()
                ->where('performance_bonus_run_id', $run->id)
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

                if (! $structure || ! $structure->is_active) {
                    continue;
                }

                $baseAmount = $this->resolveBaseAmount($employee, $run->bonus_base);

                if ($baseAmount <= 0) {
                    continue;
                }

                $band = $this->bands->matchBand($score, $run->factory_id);

                if (! $band || (float) $band->bonus_percent <= 0) {
                    continue;
                }

                $bonusAmount = round($baseAmount * ((float) $band->bonus_percent / 100), 2);

                PerformanceBonusItem::create([
                    'performance_bonus_run_id' => $run->id,
                    'employee_id'              => $employee->id,
                    'performance_review_id'    => $review->id,
                    'overall_score'            => $score,
                    'band_name'                => $band->name,
                    'bonus_percent'            => $band->bonus_percent,
                    'base_amount'              => $baseAmount,
                    'bonus_amount'             => $bonusAmount,
                    'final_amount'             => $bonusAmount,
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

    public function approve(PerformanceBonusRun $run, User $user): PerformanceBonusRun
    {
        if ($run->status !== 'calculated') {
            throw ValidationException::withMessages([
                'status' => 'Only calculated bonus runs can be approved.',
            ]);
        }

        if ($run->items()->doesntExist()) {
            throw ValidationException::withMessages([
                'items' => 'Cannot approve a bonus run with no calculated items.',
            ]);
        }

        $run->update([
            'status'      => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $run->fresh(['items.employee']);
    }

    public function updateItemOverride(PerformanceBonusItem $item, ?float $overrideAmount, ?string $notes = null): PerformanceBonusItem
    {
        $run = $item->run;

        if ($run->isApproved()) {
            throw ValidationException::withMessages([
                'override_amount' => 'Cannot override amounts on approved bonus runs.',
            ]);
        }

        $finalAmount = $overrideAmount !== null ? round($overrideAmount, 2) : (float) $item->bonus_amount;

        $item->update([
            'override_amount' => $overrideAmount !== null ? $finalAmount : null,
            'final_amount'    => $finalAmount,
            'notes'           => $notes,
        ]);

        return $item->fresh();
    }

    /** @return \Illuminate\Support\Collection<int, PerformanceReview> */
    private function eligibleReviews(PerformanceBonusRun $run): \Illuminate\Support\Collection
    {
        $query = PerformanceReview::query()
            ->with(['employee.salaryStructure'])
            ->where('factory_id', $run->factory_id)
            ->where('cycle_type', 'mid_year_6m')
            ->where('status', 'approved')
            ->whereNotNull('overall_score');

        if ($run->performance_cycle_id) {
            $query->where('cycle_id', $run->performance_cycle_id);
        } else {
            $query->whereHas('cycle', fn ($q) => $q->where('year', $run->year));
        }

        return $query->get();
    }

    private function resolveBaseAmount(Employee $employee, string $bonusBase): float
    {
        $structure = $employee->salaryStructure;

        if (! $structure) {
            return 0.0;
        }

        if ($bonusBase === 'basic') {
            $basic = $structure->headAmount('BASIC');

            if ($basic > 0) {
                return $basic;
            }

            if ((float) $structure->basic_salary > 0) {
                return (float) $structure->basic_salary;
            }

            if ($structure->pay_type === 'wages') {
                return round((float) $structure->daily_wage * 26, 2);
            }

            return 0.0;
        }

        return $structure->monthlyGross();
    }
}
