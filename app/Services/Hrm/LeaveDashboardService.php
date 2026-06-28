<?php

namespace App\Services\Hrm;

use App\Models\Hrm\LeaveApplication;
use App\Models\User;
use App\Services\Hrm\Concerns\ScopesDashboardFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveDashboardService
{
    use ScopesDashboardFactory;

    /** @return array<string, mixed> */
    public function build(User $user, ?int $factoryId, Carbon $from, Carbon $to): array
    {
        $base = $this->scopeFactoryQuery(LeaveApplication::query(), $user, $factoryId);
        $period = (clone $base)->whereBetween('applied_at', [$from, $to->copy()->endOfDay()]);

        $pipeline = (clone $period)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $onLeaveToday = (clone $base)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->count();

        $pending = (clone $base)->where('status', 'pending')->count();

        $recentApplications = (clone $period)
            ->with(['employee', 'leaveType'])
            ->latest('applied_at')
            ->limit(8)
            ->get();

        $pendingApprovals = (clone $base)
            ->with(['employee', 'leaveType'])
            ->where('status', 'pending')
            ->latest('applied_at')
            ->limit(8)
            ->get();

        return [
            'kpis' => [
                ['label' => 'Pending Approval', 'value' => $pending, 'text' => 'text-amber-700', 'panel' => 'border-amber-200 bg-amber-50/60', 'url' => route('admin.hrm.leave.transactions.index', ['status' => 'pending'])],
                ['label' => 'Approved (period)', 'value' => (int) ($pipeline['approved'] ?? 0), 'text' => 'text-emerald-700', 'panel' => 'border-emerald-200 bg-emerald-50/60'],
                ['label' => 'Rejected (period)', 'value' => (int) ($pipeline['rejected'] ?? 0), 'text' => 'text-red-700', 'panel' => 'border-red-200 bg-red-50/60'],
                ['label' => 'On Leave Today', 'value' => $onLeaveToday, 'text' => 'text-blue-700', 'panel' => 'border-blue-200 bg-blue-50/60'],
                ['label' => 'Applications (period)', 'value' => (clone $period)->count(), 'text' => 'text-brand', 'panel' => 'border-brand/20 bg-brand/5'],
            ],
            'pipeline'            => $pipeline,
            'recent_applications' => $recentApplications,
            'pending_approvals'   => $pendingApprovals,
            'statuses'            => LeaveApplication::STATUSES,
        ];
    }
}
