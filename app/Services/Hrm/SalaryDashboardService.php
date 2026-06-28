<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\SalaryStructure;
use App\Models\User;
use App\Services\Hrm\Concerns\ScopesDashboardFactory;
use Carbon\Carbon;

class SalaryDashboardService
{
    use ScopesDashboardFactory;

    /** @return array<string, mixed> */
    public function build(User $user, ?int $factoryId, Carbon $from, Carbon $to): array
    {
        $employeeBase = $this->scopeFactoryQuery(
            Employee::query()->whereIn('status', ['active', 'probation']),
            $user,
            $factoryId
        );

        $periodBase = $this->scopeFactoryQuery(PayrollPeriod::query(), $user, $factoryId);

        $activeEmployees = (clone $employeeBase)->count();

        $withStructure = SalaryStructure::query()
            ->whereHas('employee', function ($q) use ($user, $factoryId) {
                $q->whereIn('status', ['active', 'probation']);
                $this->scopeFactoryQuery($q, $user, $factoryId);
            })
            ->distinct('employee_id')
            ->count('employee_id');

        $withoutStructure = max(0, $activeEmployees - $withStructure);

        $recentPeriods = (clone $periodBase)
            ->with(['factory'])
            ->whereBetween('created_at', [$from, $to->copy()->endOfDay()])
            ->latest('year')
            ->latest('month')
            ->limit(8)
            ->get();

        $openPeriods = (clone $periodBase)
            ->whereIn('status', ['draft', 'calculated'])
            ->with(['factory'])
            ->latest('year')
            ->latest('month')
            ->limit(5)
            ->get();

        return [
            'kpis' => [
                ['label' => 'Active Employees', 'value' => $activeEmployees, 'text' => 'text-brand', 'panel' => 'border-brand/20 bg-brand/5'],
                ['label' => 'With Salary Setup', 'value' => $withStructure, 'text' => 'text-emerald-700', 'panel' => 'border-emerald-200 bg-emerald-50/60', 'url' => route('admin.hrm.salary.employee-salary.index')],
                ['label' => 'Missing Salary', 'value' => $withoutStructure, 'text' => 'text-amber-700', 'panel' => 'border-amber-200 bg-amber-50/60'],
                ['label' => 'Open Payroll Periods', 'value' => (clone $periodBase)->whereIn('status', ['draft', 'calculated'])->count(), 'text' => 'text-blue-700', 'panel' => 'border-blue-200 bg-blue-50/60', 'url' => route('admin.hrm.salary.process.index')],
                ['label' => 'Frozen (period)', 'value' => (clone $periodBase)->where('status', 'frozen')->whereBetween('frozen_at', [$from, $to->copy()->endOfDay()])->count(), 'text' => 'text-gray-700', 'panel' => 'border-gray-200 bg-gray-50/60'],
            ],
            'recent_periods' => $recentPeriods,
            'open_periods'   => $openPeriods,
        ];
    }
}
