<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\PerformanceCycle;
use App\Models\Hrm\PerformanceReview;
use App\Models\Hrm\PerformanceScore;
use App\Models\Hrm\PerformanceTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformanceReviewService
{
    public function __construct(
        private PerformanceAutoMetricsService $autoMetrics,
        private PerformanceScoreCalculator $calculator,
        private EmployeeServiceHistoryService $history,
        private HrmNotificationService $notifications,
    ) {}

    public function createForCycle(
        PerformanceCycle $cycle,
        Employee $employee,
        PerformanceTemplate $template,
        User $user,
    ): PerformanceReview {
        $periodFrom = Carbon::parse($cycle->period_from);
        $periodTo = Carbon::parse($cycle->period_to);

        $employeePeriod = $this->employeeReviewPeriod($employee, $cycle->cycle_type, $periodFrom, $periodTo);

        $status = 'pending_rating';
        $blockedReason = null;

        if ($employee->isReviewBlocked()) {
            $status = 'blocked';
            $blockedReason = 'Employee is suspended — review blocked.';
        } elseif (! $employee->reporting_to_id) {
            $status = 'draft';
            $blockedReason = 'Reporting person not assigned — assign before rating.';
        }

        $auto = $this->autoMetrics->compute($employee, $employeePeriod['from'], $employeePeriod['to']);

        $review = PerformanceReview::create([
            'factory_id'       => $cycle->factory_id,
            'cycle_id'         => $cycle->id,
            'employee_id'      => $employee->id,
            'template_id'      => $template->id,
            'cycle_type'       => $cycle->cycle_type,
            'status'           => $status,
            'period_from'      => $employeePeriod['from']->toDateString(),
            'period_to'        => $employeePeriod['to']->toDateString(),
            'reporting_to_id'  => $employee->reporting_to_id,
            'auto_metrics'     => $auto['metrics'],
            'manual_fallback'  => $auto['manual_fallback'],
            'blocked_reason'   => $blockedReason,
            'created_by'       => $user->id,
        ]);

        $this->seedScores($review, $template, $auto['scores']);

        if ($review->status === 'pending_rating') {
            $this->notifications->performanceReviewPendingRating($review);
        }

        return $review->load('scores');
    }

    public function assignReportingPerson(PerformanceReview $review, int $reportingToId): PerformanceReview
    {
        if (! in_array($review->status, ['draft', 'blocked'], true)) {
            throw ValidationException::withMessages([
                'reporting_to_id' => 'Reporting person can only be assigned on draft or blocked reviews.',
            ]);
        }

        $employee = $review->employee;

        if ($employee->isReviewBlocked()) {
            throw ValidationException::withMessages([
                'status' => 'Cannot assign reporting person while employee is suspended.',
            ]);
        }

        $review->update([
            'reporting_to_id' => $reportingToId,
            'status'          => 'pending_rating',
            'blocked_reason'  => null,
        ]);

        return $review->fresh();
    }

    /** @param array<string, float|null> $manualScores keyed by criterion code */
    public function submitRating(
        PerformanceReview $review,
        array $manualScores,
        User $user,
        ?int $onBehalfOfEmployeeId = null,
        ?string $ratingNotes = null,
        ?string $probationRecommendation = null,
        bool $applyConfirmation = false,
    ): PerformanceReview {
        if (! $review->isPendingRating()) {
            throw ValidationException::withMessages([
                'status' => 'Only reviews pending rating can be submitted.',
            ]);
        }

        if ($review->employee->isReviewBlocked()) {
            throw ValidationException::withMessages([
                'status' => 'Review is blocked because the employee is suspended.',
            ]);
        }

        return DB::transaction(function () use ($review, $manualScores, $user, $onBehalfOfEmployeeId, $ratingNotes, $probationRecommendation, $applyConfirmation) {
            $review->load('scores', 'template.criteria');

            foreach ($review->scores as $score) {
                if ($score->criterion_type === 'manual') {
                    $value = $manualScores[$score->code] ?? null;

                    if ($value === null || $value === '') {
                        throw ValidationException::withMessages([
                            "scores.{$score->code}" => "{$score->label} score is required.",
                        ]);
                    }

                    $numeric = (float) $value;

                    if ($numeric < 0 || $numeric > 100) {
                        throw ValidationException::withMessages([
                            "scores.{$score->code}" => "{$score->label} must be between 0 and 100.",
                        ]);
                    }

                    $score->update(['score' => round($numeric, 2)]);
                }
            }

            if ($review->manual_fallback) {
                foreach ($review->scores as $score) {
                    if ($score->criterion_type !== 'auto' || $score->score !== null) {
                        continue;
                    }

                    $fallback = $manualScores[$score->code] ?? null;

                    if ($fallback !== null && $fallback !== '') {
                        $score->update([
                            'score'    => round((float) $fallback, 2),
                            'is_auto'  => false,
                            'notes'    => 'Manual fallback — no attendance data.',
                        ]);
                    }
                }
            }

            $review->refresh()->load('scores');
            $overall = $this->calculator->calculateOverall($review);

            $review->update([
                'overall_score'             => $overall,
                'status'                    => 'pending_hr',
                'rated_by_user_id'          => $user->id,
                'rated_on_behalf_of_id'     => $onBehalfOfEmployeeId,
                'rated_at'                  => now(),
                'rating_notes'              => $ratingNotes,
                'probation_recommendation'  => $probationRecommendation,
                'apply_confirmation'        => $applyConfirmation && $review->cycle_type === 'probation_6m',
            ]);

            $this->notifications->performanceReviewPendingHr($review->fresh(['employee']));

            return $review->fresh(['scores', 'employee', 'reportingTo']);
        });
    }

    public function approve(PerformanceReview $review, User $user): PerformanceReview
    {
        if (! $review->isPendingHr()) {
            throw ValidationException::withMessages([
                'status' => 'Only reviews pending HR approval can be approved.',
            ]);
        }

        return DB::transaction(function () use ($review, $user) {
            $employee = $review->employee()->lockForUpdate()->firstOrFail();
            $original = $employee->getAttributes();

            $review->update([
                'status'         => 'approved',
                'hr_approved_by' => $user->id,
                'hr_approved_at' => now(),
            ]);

            $this->history->recordPerformanceReview($employee, $review);

            if ($review->cycle_type === 'probation_6m' && $review->passedMinimumScore()) {
                $employeeUpdates = ['probation_passed_at' => now()];

                if ($review->apply_confirmation) {
                    $employeeUpdates['status'] = 'active';
                    $employeeUpdates['confirmation_date'] = now()->toDateString();
                }

                $employee->update($employeeUpdates);
                $this->history->recordChanges($employee, $original);
            }

            $this->notifications->performanceReviewApproved($review->fresh(['employee']));

            return $review->fresh(['employee', 'scores']);
        });
    }

    public function reject(PerformanceReview $review, User $user, string $reason): PerformanceReview
    {
        if (! $review->isPendingHr()) {
            throw ValidationException::withMessages([
                'status' => 'Only reviews pending HR approval can be rejected.',
            ]);
        }

        $review->update([
            'status'               => 'rejected',
            'hr_rejected_by'       => $user->id,
            'hr_rejected_at'       => now(),
            'hr_rejection_reason'  => $reason,
        ]);

        $this->notifications->performanceReviewRejected($review->fresh(['employee']));

        return $review->fresh();
    }

    public function cancel(PerformanceReview $review): PerformanceReview
    {
        if ($review->isApproved()) {
            throw ValidationException::withMessages([
                'status' => 'Approved reviews cannot be cancelled.',
            ]);
        }

        $review->update(['status' => 'cancelled']);

        return $review->fresh();
    }

    public function recalculateAutoScores(PerformanceReview $review): PerformanceReview
    {
        if ($review->isApproved()) {
            throw ValidationException::withMessages([
                'status' => 'Cannot recalculate auto scores on approved reviews.',
            ]);
        }

        $employee = $review->employee;
        $from = Carbon::parse($review->period_from);
        $to = Carbon::parse($review->period_to);

        $auto = $this->autoMetrics->compute($employee, $from, $to);

        $review->update([
            'auto_metrics'    => $auto['metrics'],
            'manual_fallback' => $auto['manual_fallback'],
        ]);

        foreach ($review->scores as $score) {
            if ($score->criterion_type !== 'auto') {
                continue;
            }

            $autoScore = $auto['scores'][$score->code] ?? null;

            $score->update([
                'score'       => $autoScore,
                'is_auto'     => true,
                'auto_source' => $auto['metrics'],
            ]);
        }

        return $review->fresh('scores');
    }

    /** @return array{from: Carbon, to: Carbon} */
    private function employeeReviewPeriod(Employee $employee, string $cycleType, Carbon $periodFrom, Carbon $periodTo): array
    {
        if ($cycleType === 'probation_6m' && $employee->joining_date) {
            $join = Carbon::parse($employee->joining_date);

            return [
                'from' => $join,
                'to'   => $join->copy()->addMonths(6)->subDay()->min($periodTo),
            ];
        }

        return ['from' => $periodFrom, 'to' => $periodTo];
    }

    /** @param array<string, float|null> $autoScores */
    private function seedScores(PerformanceReview $review, PerformanceTemplate $template, array $autoScores): void
    {
        foreach ($template->criteria as $criterion) {
            $isAuto = $criterion->criterion_type === 'auto';
            $score = $isAuto ? ($autoScores[$criterion->code] ?? null) : null;

            PerformanceScore::create([
                'review_id'      => $review->id,
                'criterion_id'   => $criterion->id,
                'code'           => $criterion->code,
                'label'          => $criterion->label,
                'criterion_type' => $criterion->criterion_type,
                'weight'         => $criterion->weight,
                'score'          => $score,
                'is_auto'        => $isAuto && $score !== null,
                'auto_source'    => $isAuto ? $review->auto_metrics : null,
            ]);
        }
    }
}
