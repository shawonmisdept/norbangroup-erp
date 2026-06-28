<?php

namespace App\Services\Hrm;

use App\Models\Hrm\DisciplinaryRecord;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePromotion;
use App\Models\Hrm\EmployeeSeparation;
use App\Models\User;
use App\Services\Hrm\Concerns\ScopesDashboardFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeDashboardService
{
    use ScopesDashboardFactory;

    /** @return array<string, mixed> */
    public function build(User $user, ?int $factoryId, Carbon $from, Carbon $to): array
    {
        $base = $this->scopeFactoryQuery(Employee::query(), $user, $factoryId);
        $active = (clone $base)->whereIn('status', ['active', 'probation']);

        $periodJoinings = (clone $base)
            ->whereBetween('joining_date', [$from->toDateString(), $to->toDateString()])
            ->count();

        $separationBase = $this->scopeFactoryQuery(EmployeeSeparation::query(), $user, $factoryId);
        $promotionBase = $this->scopeFactoryQuery(EmployeePromotion::query(), $user, $factoryId);
        $disciplineBase = $this->scopeFactoryQuery(DisciplinaryRecord::query(), $user, $factoryId);

        $recentJoinings = (clone $base)
            ->with(['factory', 'department', 'designation'])
            ->whereNotNull('joining_date')
            ->whereBetween('joining_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('joining_date')
            ->limit(8)
            ->get();

        $pendingSeparations = (clone $separationBase)
            ->with(['employee'])
            ->where('status', 'pending')
            ->latest('id')
            ->limit(8)
            ->get();

        $pendingPromotions = (clone $promotionBase)
            ->with(['employee'])
            ->where('status', 'pending')
            ->latest('id')
            ->limit(8)
            ->get();

        $statusBreakdown = (clone $active)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'kpis' => [
                ['label' => 'Active', 'value' => (clone $active)->where('status', 'active')->count(), 'text' => 'text-emerald-700', 'panel' => 'border-emerald-200 bg-emerald-50/60', 'url' => route('admin.hrm.employees.index', ['status' => 'active'])],
                ['label' => 'Probation', 'value' => (clone $active)->where('status', 'probation')->count(), 'text' => 'text-amber-700', 'panel' => 'border-amber-200 bg-amber-50/60', 'url' => route('admin.hrm.employees.index', ['status' => 'probation'])],
                ['label' => 'New Joinings', 'value' => $periodJoinings, 'text' => 'text-brand', 'panel' => 'border-brand/20 bg-brand/5'],
                ['label' => 'Pending Exit', 'value' => (clone $separationBase)->where('status', 'pending')->count(), 'text' => 'text-red-700', 'panel' => 'border-red-200 bg-red-50/60', 'url' => route('admin.hrm.separations.index', ['status' => 'pending'])],
                ['label' => 'Pending Promotion', 'value' => (clone $promotionBase)->where('status', 'pending')->count(), 'text' => 'text-indigo-700', 'panel' => 'border-indigo-200 bg-indigo-50/60', 'url' => route('admin.hrm.promotions.index', ['status' => 'pending'])],
                ['label' => 'Discipline (period)', 'value' => (clone $disciplineBase)->whereBetween('incident_date', [$from->toDateString(), $to->toDateString()])->count(), 'text' => 'text-orange-700', 'panel' => 'border-orange-200 bg-orange-50/60', 'url' => route('admin.hrm.discipline.index')],
            ],
            'status_breakdown'    => $statusBreakdown,
            'recent_joinings'     => $recentJoinings,
            'pending_separations' => $pendingSeparations,
            'pending_promotions'  => $pendingPromotions,
        ];
    }
}
