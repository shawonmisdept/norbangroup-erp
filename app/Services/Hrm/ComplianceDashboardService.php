<?php

namespace App\Services\Hrm;

use App\Models\Hrm\BonusRun;
use App\Models\Hrm\GratuitySettlement;
use App\Models\User;
use App\Services\Hrm\Concerns\ScopesDashboardFactory;
use Carbon\Carbon;

class ComplianceDashboardService
{
    use ScopesDashboardFactory;

    /** @return array<string, mixed> */
    public function build(User $user, ?int $factoryId, Carbon $from, Carbon $to): array
    {
        $bonusBase = $this->scopeFactoryQuery(BonusRun::query(), $user, $factoryId);
        $gratuityBase = $this->scopeFactoryQuery(GratuitySettlement::query(), $user, $factoryId);

        $recentBonusRuns = (clone $bonusBase)
            ->with('factory')
            ->whereBetween('created_at', [$from, $to->copy()->endOfDay()])
            ->latest('id')
            ->limit(8)
            ->get();

        $pendingGratuity = (clone $gratuityBase)
            ->with(['employee'])
            ->where('status', 'calculated')
            ->latest('id')
            ->limit(8)
            ->get();

        return [
            'kpis' => [
                ['label' => 'Festival Bonus (draft)', 'value' => (clone $bonusBase)->where('status', 'draft')->count(), 'text' => 'text-amber-700', 'panel' => 'border-amber-200 bg-amber-50/60', 'url' => route('admin.hrm.compliance.bonus.index', ['status' => 'draft'])],
                ['label' => 'Bonus Approved (period)', 'value' => (clone $bonusBase)->where('status', 'approved')->whereBetween('updated_at', [$from, $to->copy()->endOfDay()])->count(), 'text' => 'text-emerald-700', 'panel' => 'border-emerald-200 bg-emerald-50/60'],
                ['label' => 'Gratuity Pending', 'value' => (clone $gratuityBase)->where('status', 'calculated')->count(), 'text' => 'text-indigo-700', 'panel' => 'border-indigo-200 bg-indigo-50/60', 'url' => route('admin.hrm.compliance.gratuity.index')],
                ['label' => 'Gratuity Paid (period)', 'value' => (clone $gratuityBase)->where('status', 'paid')->whereBetween('paid_at', [$from, $to->copy()->endOfDay()])->count(), 'text' => 'text-brand', 'panel' => 'border-brand/20 bg-brand/5'],
            ],
            'recent_bonus_runs' => $recentBonusRuns,
            'pending_gratuity'  => $pendingGratuity,
        ];
    }
}
