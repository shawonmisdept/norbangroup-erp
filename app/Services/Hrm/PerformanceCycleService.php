<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\PerformanceCycle;
use App\Models\Hrm\PerformanceReview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformanceCycleService
{
    public function __construct(
        private PerformanceTemplateService $templates,
        private PerformanceReviewService $reviews,
    ) {}

    public function open(array $data, User $user): PerformanceCycle
    {
        $cycleType = $data['cycle_type'];
        $factoryId = (int) $data['factory_id'];
        $periodFrom = Carbon::parse($data['period_from']);
        $periodTo = Carbon::parse($data['period_to']);

        if ($periodTo->lt($periodFrom)) {
            throw ValidationException::withMessages([
                'period_to' => 'Review period end must be on or after the start date.',
            ]);
        }

        $template = isset($data['template_id'])
            ? \App\Models\Hrm\PerformanceTemplate::with('criteria')->findOrFail($data['template_id'])
            : $this->templates->resolveForFactory($factoryId, $cycleType);

        return DB::transaction(function () use ($data, $user, $cycleType, $factoryId, $periodFrom, $periodTo, $template) {
            $cycle = PerformanceCycle::create([
                'factory_id'  => $factoryId,
                'cycle_type'  => $cycleType,
                'name'        => $data['name'],
                'year'        => $data['year'] ?? null,
                'period_from' => $periodFrom->toDateString(),
                'period_to'   => $periodTo->toDateString(),
                'status'      => 'open',
                'template_id' => $template->id,
                'opened_by'   => $user->id,
                'opened_at'   => now(),
                'notes'       => $data['notes'] ?? null,
            ]);

            $employees = $this->eligibleEmployees($factoryId, $cycleType, $periodFrom, $periodTo);
            $count = 0;

            foreach ($employees as $employee) {
                if ($this->reviewExists($employee, $cycleType, $periodFrom, $periodTo)) {
                    continue;
                }

                $this->reviews->createForCycle($cycle, $employee, $template, $user);
                $count++;
            }

            $cycle->update(['review_count' => $count]);

            return $cycle->fresh(['template', 'factory']);
        });
    }

    public function close(PerformanceCycle $cycle): PerformanceCycle
    {
        if (! $cycle->isOpen()) {
            throw ValidationException::withMessages([
                'status' => 'Only open cycles can be closed.',
            ]);
        }

        $cycle->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);

        return $cycle->fresh();
    }

    /** @return array{period_from: string, period_to: string, name: string, year: int|null} */
    public function suggestPeriod(string $cycleType, ?int $year = null): array
    {
        $year = $year ?? (int) now()->year;

        return match ($cycleType) {
            'probation_6m' => [
                'name'        => 'Probation Reviews',
                'year'        => $year,
                'period_from' => now()->subMonths(6)->startOfMonth()->toDateString(),
                'period_to'   => now()->toDateString(),
            ],
            'mid_year_6m' => [
                'name'        => "Mid-Year Performance {$year}",
                'year'        => $year,
                'period_from' => Carbon::create($year - 1, 7, 1)->toDateString(),
                'period_to'   => Carbon::create($year - 1, 12, 31)->toDateString(),
            ],
            'annual_12m' => [
                'name'        => "Annual Increment Reviews {$year}",
                'year'        => $year,
                'period_from' => Carbon::create($year - 1, 1, 1)->toDateString(),
                'period_to'   => Carbon::create($year - 1, 12, 31)->toDateString(),
            ],
            default => [
                'name'        => 'Performance Cycle',
                'year'        => $year,
                'period_from' => now()->subMonths(6)->toDateString(),
                'period_to'   => now()->toDateString(),
            ],
        };
    }

    /** @return \Illuminate\Support\Collection<int, Employee> */
    private function eligibleEmployees(int $factoryId, string $cycleType, Carbon $periodFrom, Carbon $periodTo): \Illuminate\Support\Collection
    {
        $query = Employee::query()
            ->where('factory_id', $factoryId)
            ->whereNotIn('status', Employee::SEPARATED_STATUSES);

        $employees = match ($cycleType) {
            'probation_6m' => $query
                ->whereNull('probation_passed_at')
                ->whereIn('status', ['probation', 'active', 'suspended'])
                ->get()
                ->filter(function (Employee $e) use ($periodFrom, $periodTo) {
                    if (! $e->joining_date) {
                        return false;
                    }

                    $anniversary = Carbon::parse($e->joining_date)->addMonths(6);

                    return $anniversary->gte($periodFrom) && $anniversary->lte($periodTo);
                }),

            'mid_year_6m' => $query
                ->where('status', 'active')
                ->whereNotNull('probation_passed_at')
                ->get(),

            'annual_12m' => $query
                ->where('status', 'active')
                ->whereNotNull('probation_passed_at')
                ->whereDate('joining_date', '<=', $periodTo->toDateString())
                ->get()
                ->filter(fn (Employee $e) => $this->isAnniversaryDue($e, $periodFrom, $periodTo, 12)),

            default => collect(),
        };

        return $employees instanceof \Illuminate\Support\Collection ? $employees : collect($employees);
    }

    private function isAnniversaryDue(Employee $employee, Carbon $periodFrom, Carbon $periodTo, int $months): bool
    {
        if (! $employee->joining_date) {
            return false;
        }

        $join = Carbon::parse($employee->joining_date);
        $cursor = $join->copy()->addMonths($months);

        while ($cursor->lte($periodTo)) {
            if ($cursor->between($periodFrom, $periodTo)) {
                return true;
            }
            $cursor->addMonths($months);
        }

        return false;
    }

    private function reviewExists(Employee $employee, string $cycleType, Carbon $periodFrom, Carbon $periodTo): bool
    {
        return PerformanceReview::query()
            ->where('employee_id', $employee->id)
            ->where('cycle_type', $cycleType)
            ->where('period_from', $periodFrom->toDateString())
            ->where('period_to', $periodTo->toDateString())
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->exists();
    }
}
