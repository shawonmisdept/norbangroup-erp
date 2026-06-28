<?php

namespace App\Services\Hrm;

use App\Models\Hrm\FinalSettlement;
use App\Models\Hrm\LoanAccount;
use App\Models\User;
use App\Services\Hrm\Concerns\ScopesDashboardFactory;
use Carbon\Carbon;

class FinanceDashboardService
{
    use ScopesDashboardFactory;

    public function __construct(private HrmDashboardService $hrmDashboard) {}

    /** @return array<string, mixed> */
    public function build(User $user, ?int $factoryId, Carbon $from, Carbon $to): array
    {
        $factoryIds = $factoryId
            ? [$factoryId]
            : ($user->factory_id ? [$user->factory_id] : $this->hrmDashboard->accessibleFactories($user)->pluck('id')->all());

        $financeStats = $this->hrmDashboard->financeStats($factoryIds, $to);

        $loanBase = $this->scopeFactoryQuery(LoanAccount::query(), $user, $factoryId);
        $settlementBase = $this->scopeFactoryQuery(FinalSettlement::query(), $user, $factoryId);

        $recentLoans = (clone $loanBase)
            ->with(['employee'])
            ->whereBetween('created_at', [$from, $to->copy()->endOfDay()])
            ->latest('id')
            ->limit(8)
            ->get();

        $pendingSettlements = (clone $settlementBase)
            ->with(['employee'])
            ->whereIn('status', ['draft', 'calculated', 'approved'])
            ->latest('id')
            ->limit(8)
            ->get();

        return [
            'kpis' => [
                ['label' => 'Pending Loans', 'value' => $financeStats['pending_loans'], 'text' => 'text-amber-700', 'panel' => 'border-amber-200 bg-amber-50/60', 'url' => route('admin.hrm.finance.loans.index', ['status' => 'pending'])],
                ['label' => 'Active Loans', 'value' => $financeStats['active_loans'], 'text' => 'text-orange-700', 'panel' => 'border-orange-200 bg-orange-50/60', 'url' => route('admin.hrm.finance.loans.index', ['status' => 'active'])],
                ['label' => 'Outstanding', 'value' => '৳' . number_format($financeStats['active_loan_balance'], 0), 'text' => 'text-red-700', 'panel' => 'border-red-200 bg-red-50/60'],
                ['label' => 'F&F Pending', 'value' => $financeStats['pending_final_settlements'], 'text' => 'text-sky-700', 'panel' => 'border-sky-200 bg-sky-50/60', 'url' => route('admin.hrm.finance.final-settlement.index')],
                ['label' => 'Month TDS', 'value' => '৳' . number_format($financeStats['month_tds'], 0), 'text' => 'text-indigo-700', 'panel' => 'border-indigo-200 bg-indigo-50/60', 'url' => route('admin.hrm.finance.tax.index')],
                ['label' => 'Month PF (Employer)', 'value' => '৳' . number_format($financeStats['month_pf_employer'], 0), 'text' => 'text-emerald-700', 'panel' => 'border-emerald-200 bg-emerald-50/60', 'url' => route('admin.hrm.finance.pf.index')],
            ],
            'recent_loans'        => $recentLoans,
            'pending_settlements' => $pendingSettlements,
        ];
    }
}
