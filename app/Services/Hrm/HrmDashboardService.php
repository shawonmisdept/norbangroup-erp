<?php

namespace App\Services\Hrm;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeeTaxLedger;
use App\Models\Hrm\FinalSettlement;
use App\Models\Hrm\LoanAccount;
use App\Models\Hrm\PfContribution;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HrmDashboardService
{
    public const TODAY_ATTENDANCE_TYPES = [
        'all'           => 'All Employees',
        'present'       => 'Total Present',
        'male_present'  => 'Male Present',
        'female_present'=> 'Female Present',
        'absent'        => 'Total Absent',
    ];

    public function __construct(private AttendanceReportService $attendanceReports) {}

    /**
     * @return array{
     *     date_label: string,
     *     employee_stats: array<string, int>,
     *     today_stats: array<string, int>,
     *     today_departments: Collection<int, object>,
     *     today_leave: Collection<int, object>,
     *     today_shifts: Collection<int, object>,
     *     finance_stats: array<string, int|float>|null,
     * }
     */
    public function buildOverview(?User $user, ?int $factoryId, Carbon $date): array
    {
        $factoryIds = $this->accessibleFactoryIds($user, $factoryId);

        return [
            'date_label'        => $date->format('d M Y'),
            'employee_stats'    => $this->employeeStats($factoryIds),
            'today_stats'       => $this->todayAttendanceStats($factoryIds, $date),
            'today_departments' => $this->todayByDepartment($factoryIds, $date),
            'today_leave'       => $this->todayLeaveByDepartment($factoryIds, $date),
            'today_shifts'      => $this->todayByShift($factoryIds, $date),
            'finance_stats'     => $user?->hasAnyFinanceViewPermission()
                ? $this->financeStats($factoryIds, $date)
                : null,
        ];
    }

    /** @return array{pending_loans: int, active_loans: int, active_loan_balance: float, month_tds: float, month_pf_employer: float, pending_final_settlements: int} */
    public function financeStats(array $factoryIds, Carbon $date): array
    {
        $loanBase = LoanAccount::query()
            ->when($factoryIds !== [], fn ($q) => $q->whereIn('factory_id', $factoryIds));

        $settlementBase = FinalSettlement::query()
            ->when($factoryIds !== [], fn ($q) => $q->whereIn('factory_id', $factoryIds));

        $monthTds = EmployeeTaxLedger::query()
            ->when($factoryIds !== [], fn ($q) => $q->whereIn('factory_id', $factoryIds))
            ->where('year', $date->year)
            ->where('month', $date->month)
            ->sum('tds_amount');

        $monthPfEmployer = PfContribution::query()
            ->where('year', $date->year)
            ->where('month', $date->month)
            ->when($factoryIds !== [], fn ($q) => $q->whereHas(
                'account',
                fn ($inner) => $inner->whereIn('factory_id', $factoryIds)
            ))
            ->sum('employer_amount');

        return [
            'pending_loans'             => (int) (clone $loanBase)->where('status', 'pending')->count(),
            'active_loans'              => (int) (clone $loanBase)->where('status', 'active')->count(),
            'active_loan_balance'       => round((float) (clone $loanBase)->where('status', 'active')->sum('balance'), 2),
            'month_tds'                 => round((float) $monthTds, 2),
            'month_pf_employer'         => round((float) $monthPfEmployer, 2),
            'pending_final_settlements' => (int) (clone $settlementBase)->whereIn('status', ['draft', 'calculated', 'approved'])->count(),
        ];
    }

    public function todayAttendanceDetail(
        ?User $user,
        ?int $factoryId,
        Carbon $date,
        string $type,
        array $filters = []
    ): LengthAwarePaginator {
        $factoryIds = $this->accessibleFactoryIds($user, $factoryId);

        $query = AttendanceDailyLog::query()
            ->with([
                'employee.factory',
                'employee.department',
                'employee.designation',
                'employee.line',
                'lateAcceptanceApplication',
            ])
            ->whereDate('attendance_date', $date->toDateString())
            ->when($factoryIds !== [], fn ($q) => $q->whereIn('factory_id', $factoryIds))
            ->whereHas('employee');

        $this->applyTodayTypeFilter($query, $type);
        $this->applyDetailFilters($query, $filters);

        return $query
            ->orderBy(
                Employee::select('name')
                    ->whereColumn('hrm_employees.id', 'hrm_attendance_daily_logs.employee_id')
                    ->limit(1)
            )
            ->paginate(25)
            ->withQueryString();
    }

    /** @return list<int> */
    private function accessibleFactoryIds(?User $user, ?int $factoryId): array
    {
        return $this->accessibleFactories($user, $factoryId)->pluck('id')->all();
    }

    /** @return Collection<int, Factory> */
    public function accessibleFactories(?User $user, ?int $factoryId = null): Collection
    {
        $query = Factory::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($user?->factory_id) {
            $query->where('id', $user->factory_id);
        } elseif ($factoryId) {
            $query->where('id', $factoryId);
        }

        return $query->get(['id', 'name']);
    }

    /** @return array<string, int> */
    private function employeeStats(array $factoryIds): array
    {
        $base = Employee::query()->when($factoryIds !== [], fn ($q) => $q->whereIn('factory_id', $factoryIds));

        $active = (clone $base)->whereIn('status', ['active', 'probation']);

        return [
            'total'      => (clone $active)->count(),
            'male'       => (clone $active)->where('gender', 'male')->count(),
            'female'     => (clone $active)->where('gender', 'female')->count(),
            'other'      => (clone $active)->where('gender', 'other')->count(),
            'separated'  => (clone $base)->whereIn('status', ['terminated', 'resigned'])->count(),
        ];
    }

    /** @return array<string, int> */
    private function todayAttendanceStats(array $factoryIds, Carbon $date): array
    {
        $presentStatuses = ['present', 'late', 'half_day'];

        $base = DB::table('hrm_attendance_daily_logs as l')
            ->join('hrm_employees as e', 'e.id', '=', 'l.employee_id')
            ->whereDate('l.attendance_date', $date->toDateString())
            ->when($factoryIds !== [], fn ($q) => $q->whereIn('l.factory_id', $factoryIds));

        $present = (int) (clone $base)->whereIn('l.status', $presentStatuses)->count();
        $malePresent = (int) (clone $base)
            ->whereIn('l.status', $presentStatuses)
            ->where('e.gender', 'male')
            ->count();
        $femalePresent = (int) (clone $base)
            ->whereIn('l.status', $presentStatuses)
            ->where('e.gender', 'female')
            ->count();
        $absent = (int) (clone $base)->where('l.status', 'absent')->count();

        return [
            'present'        => $present,
            'male_present'   => $malePresent,
            'female_present' => $femalePresent,
            'absent'         => $absent,
        ];
    }

    /** @return Collection<int, object> */
    private function todayByDepartment(array $factoryIds, Carbon $date): Collection
    {
        return DB::table('hrm_attendance_daily_logs as l')
            ->join('hrm_employees as e', 'e.id', '=', 'l.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->whereDate('l.attendance_date', $date->toDateString())
            ->when($factoryIds !== [], fn ($q) => $q->whereIn('l.factory_id', $factoryIds))
            ->groupBy('e.department_id', 'd.name')
            ->orderBy('d.name')
            ->select([
                'e.department_id',
                DB::raw("COALESCE(d.name, 'Unassigned') as department_name"),
                DB::raw("SUM(CASE WHEN l.status IN ('present','late','half_day') THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN l.status IN ('present','late','half_day') AND e.gender = 'male' THEN 1 ELSE 0 END) as male_count"),
                DB::raw("SUM(CASE WHEN l.status IN ('present','late','half_day') AND e.gender = 'female' THEN 1 ELSE 0 END) as female_count"),
                DB::raw("SUM(CASE WHEN l.status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
            ])
            ->get();
    }

    /** @return Collection<int, object> */
    private function todayLeaveByDepartment(array $factoryIds, Carbon $date): Collection
    {
        return DB::table('hrm_attendance_daily_logs as l')
            ->join('hrm_employees as e', 'e.id', '=', 'l.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->whereDate('l.attendance_date', $date->toDateString())
            ->where('l.status', 'leave')
            ->when($factoryIds !== [], fn ($q) => $q->whereIn('l.factory_id', $factoryIds))
            ->groupBy('e.department_id', 'd.name')
            ->orderBy('d.name')
            ->select([
                'e.department_id',
                DB::raw("COALESCE(d.name, 'Unassigned') as department_name"),
                DB::raw('COUNT(*) as leave_count'),
            ])
            ->get();
    }

    /** @return Collection<int, object> */
    private function todayByShift(array $factoryIds, Carbon $date): Collection
    {
        return DB::table('hrm_attendance_daily_logs as l')
            ->leftJoin('hrm_shifts as s', 's.id', '=', 'l.shift_id')
            ->whereDate('l.attendance_date', $date->toDateString())
            ->when($factoryIds !== [], fn ($q) => $q->whereIn('l.factory_id', $factoryIds))
            ->groupBy('l.shift_id', 's.name')
            ->orderBy('s.name')
            ->select([
                'l.shift_id',
                DB::raw("COALESCE(s.name, 'Unassigned') as shift_name"),
                DB::raw("SUM(CASE WHEN l.status IN ('present','late','half_day') THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN l.status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw('COUNT(*) as total_count'),
            ])
            ->get();
    }

    private function applyTodayTypeFilter($query, string $type): void
    {
        match ($type) {
            'present'        => $query->whereIn('hrm_attendance_daily_logs.status', ['present', 'late', 'half_day']),
            'male_present'   => $query->whereIn('hrm_attendance_daily_logs.status', ['present', 'late', 'half_day'])
                ->whereHas('employee', fn ($q) => $q->where('gender', 'male')),
            'female_present' => $query->whereIn('hrm_attendance_daily_logs.status', ['present', 'late', 'half_day'])
                ->whereHas('employee', fn ($q) => $q->where('gender', 'female')),
            'absent'         => $query->where('hrm_attendance_daily_logs.status', 'absent'),
            default          => null,
        };
    }

    private function applyDetailFilters($query, array $filters): void
    {
        if ($search = trim($filters['search'] ?? '')) {
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        if ($code = trim($filters['employee_code'] ?? '')) {
            $query->whereHas('employee', fn ($q) => $q->where('employee_code', 'like', "%{$code}%"));
        }

        if ($name = trim($filters['name'] ?? '')) {
            $query->whereHas('employee', fn ($q) => $q->where('name', 'like', "%{$name}%"));
        }

        if ($department = trim($filters['department'] ?? '')) {
            $query->whereHas('employee.department', fn ($q) => $q->where('name', 'like', "%{$department}%"));
        }

        if ($designation = trim($filters['designation'] ?? '')) {
            $query->whereHas('employee.designation', fn ($q) => $q->where('name', 'like', "%{$designation}%"));
        }

        if ($line = trim($filters['line'] ?? '')) {
            $query->whereHas('employee.line', fn ($q) => $q->where('name', 'like', "%{$line}%"));
        }

        if ($status = trim($filters['status'] ?? '')) {
            $query->where('hrm_attendance_daily_logs.status', $status);
        }
    }
}
