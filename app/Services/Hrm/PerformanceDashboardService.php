<?php

namespace App\Services\Hrm;

use App\Models\Hrm\PerformanceBonusRun;
use App\Models\Hrm\PerformanceCycle;
use App\Models\Hrm\PerformanceIncrementRun;
use App\Models\Hrm\PerformanceReview;
use App\Models\User;
use App\Services\Hrm\Concerns\ScopesDashboardFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PerformanceDashboardService
{
    use ScopesDashboardFactory;

    public function __construct(private HrmDashboardService $hrmDashboard) {}

    /** @return array<string, mixed> */
    public function build(User $user, ?int $factoryId, Carbon $from, Carbon $to): array
    {
        $reviewBase = $this->scopeFactoryQuery(PerformanceReview::query(), $user, $factoryId);
        $cycleBase = $this->scopeFactoryQuery(PerformanceCycle::query(), $user, $factoryId);
        $bonusBase = $this->scopeFactoryQuery(PerformanceBonusRun::query(), $user, $factoryId);
        $incrementBase = $this->scopeFactoryQuery(PerformanceIncrementRun::query(), $user, $factoryId);

        $factoryIds = $factoryId
            ? [$factoryId]
            : ($user->factory_id ? [$user->factory_id] : $this->hrmDashboard->accessibleFactories($user)->pluck('id')->all());

        $perfStats = $this->hrmDashboard->performanceStats($factoryIds);

        $pipeline = (clone $reviewBase)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentReviews = (clone $reviewBase)
            ->with(['employee', 'cycle'])
            ->whereBetween('created_at', [$from, $to->copy()->endOfDay()])
            ->latest('id')
            ->limit(8)
            ->get();

        $openCycles = (clone $cycleBase)
            ->where('status', 'open')
            ->with('factory')
            ->latest('id')
            ->limit(5)
            ->get();

        return [
            'kpis' => [
                ['label' => 'Pending Rating', 'value' => $perfStats['pending_rating'], 'text' => 'text-amber-700', 'panel' => 'border-amber-200 bg-amber-50/60', 'url' => route('admin.hrm.performance.reviews.index', ['pending_rating' => 1])],
                ['label' => 'Pending HR', 'value' => $perfStats['pending_hr'], 'text' => 'text-blue-700', 'panel' => 'border-blue-200 bg-blue-50/60', 'url' => route('admin.hrm.performance.reviews.index', ['pending_hr' => 1])],
                ['label' => 'Approved (month)', 'value' => $perfStats['approved_month'], 'text' => 'text-emerald-700', 'panel' => 'border-emerald-200 bg-emerald-50/60'],
                ['label' => 'Open Cycles', 'value' => $perfStats['open_cycles'], 'text' => 'text-brand', 'panel' => 'border-brand/20 bg-brand/5', 'url' => route('admin.hrm.performance.cycles.index', ['status' => 'open'])],
                ['label' => 'Bonus Runs (draft)', 'value' => (clone $bonusBase)->where('status', 'draft')->count(), 'text' => 'text-indigo-700', 'panel' => 'border-indigo-200 bg-indigo-50/60', 'url' => route('admin.hrm.performance.bonus-runs.index', ['status' => 'draft'])],
                ['label' => 'Increment Runs (draft)', 'value' => (clone $incrementBase)->where('status', 'draft')->count(), 'text' => 'text-violet-700', 'panel' => 'border-violet-200 bg-violet-50/60', 'url' => route('admin.hrm.performance.increment-runs.index', ['status' => 'draft'])],
            ],
            'pipeline'       => $pipeline,
            'recent_reviews'   => $recentReviews,
            'open_cycles'      => $openCycles,
            'review_statuses'  => PerformanceReview::STATUSES,
        ];
    }
}
