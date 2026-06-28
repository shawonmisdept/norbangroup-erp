<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\LateAcceptanceApplication;
use App\Models\User;
use App\Services\Hrm\Concerns\ScopesDashboardFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceDashboardService
{
    use ScopesDashboardFactory;

    public function __construct(private HrmDashboardService $hrmDashboard) {}

    /** @return array<string, mixed> */
    public function build(User $user, ?int $factoryId, Carbon $from, Carbon $to): array
    {
        $snapshotDate = $to->copy();
        $factoryIds = $factoryId
            ? [$factoryId]
            : ($user->factory_id ? [$user->factory_id] : []);

        if ($factoryId === null && ! $user->factory_id) {
            $factoryIds = $this->hrmDashboard->accessibleFactories($user)->pluck('id')->all();
        }

        $todayStats = $this->hrmDashboard->buildOverview($user, $factoryId, $snapshotDate);

        $dailyBase = AttendanceDailyLog::query()
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()]);
        $this->scopeFactoryQuery($dailyBase, $user, $factoryId);

        $lateCount = (clone $dailyBase)->where('status', 'late')->count();
        $halfDayCount = (clone $dailyBase)->where('status', 'half_day')->count();

        $lateAcceptanceBase = $this->scopeFactoryQuery(LateAcceptanceApplication::query(), $user, $factoryId);
        $periodBase = $this->scopeFactoryQuery(AttendancePeriod::query(), $user, $factoryId);

        $pendingLateAcceptance = (clone $lateAcceptanceBase)
            ->with(['employee', 'dailyLog'])
            ->where('status', 'pending')
            ->latest('id')
            ->limit(8)
            ->get();

        $openPeriods = (clone $periodBase)
            ->whereIn('status', ['draft', 'processed'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(5)
            ->get();

        return [
            'snapshot_date'         => $snapshotDate->format('d M Y'),
            'today_stats'           => $todayStats['today_stats'],
            'today_departments'     => $todayStats['today_departments'],
            'kpis' => [
                ['label' => 'Present Today', 'value' => $todayStats['today_stats']['present'], 'text' => 'text-emerald-700', 'panel' => 'border-emerald-200 bg-emerald-50/60', 'url' => route('admin.hrm.attendance.daily', ['date' => $snapshotDate->toDateString()])],
                ['label' => 'Absent Today', 'value' => $todayStats['today_stats']['absent'], 'text' => 'text-red-700', 'panel' => 'border-red-200 bg-red-50/60'],
                ['label' => 'Late (period)', 'value' => $lateCount, 'text' => 'text-amber-700', 'panel' => 'border-amber-200 bg-amber-50/60'],
                ['label' => 'Half Day (period)', 'value' => $halfDayCount, 'text' => 'text-orange-700', 'panel' => 'border-orange-200 bg-orange-50/60'],
                ['label' => 'Pending Late Accept', 'value' => (clone $lateAcceptanceBase)->where('status', 'pending')->count(), 'text' => 'text-blue-700', 'panel' => 'border-blue-200 bg-blue-50/60', 'url' => route('admin.hrm.attendance.late-acceptance.index', ['status' => 'pending'])],
                ['label' => 'Open Periods', 'value' => (clone $periodBase)->whereIn('status', ['draft', 'processed'])->count(), 'text' => 'text-gray-700', 'panel' => 'border-gray-200 bg-gray-50/60', 'url' => route('admin.hrm.attendance.periods')],
            ],
            'pending_late_acceptance' => $pendingLateAcceptance,
            'open_periods'            => $openPeriods,
        ];
    }
}
