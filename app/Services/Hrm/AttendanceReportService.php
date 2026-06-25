<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceReportService
{
    /** @return array<string, int|float> */
    public function monthlySummary(int $factoryId, Carbon $from, Carbon $to): array
    {
        $rows = AttendanceDailyLog::query()
            ->where('factory_id', $factoryId)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = (int) ($rows['present'] ?? 0);
        $late = (int) ($rows['late'] ?? 0);

        return [
            'present'   => $present + $late,
            'late'      => $late,
            'absent'    => (int) ($rows['absent'] ?? 0),
            'half_day'  => (int) ($rows['half_day'] ?? 0),
            'leave'     => (int) ($rows['leave'] ?? 0),
            'off_day'   => (int) ($rows['off_day'] ?? 0),
            'holiday'   => (int) ($rows['holiday'] ?? 0),
            'employees' => (int) AttendanceDailyLog::query()
                ->where('factory_id', $factoryId)
                ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
                ->distinct('employee_id')
                ->count('employee_id'),
            'total_logs' => (int) $rows->sum(),
        ];
    }

    /** @return Collection<int, object> */
    public function byDepartment(int $factoryId, Carbon $from, Carbon $to): Collection
    {
        return DB::table('hrm_attendance_daily_logs as l')
            ->join('hrm_employees as e', 'e.id', '=', 'l.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->where('l.factory_id', $factoryId)
            ->whereBetween('l.attendance_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('e.department_id', 'd.name')
            ->orderBy('d.name')
            ->select([
                'e.department_id',
                DB::raw("COALESCE(d.name, 'Unassigned') as department_name"),
                DB::raw("SUM(CASE WHEN l.status IN ('present','late') THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN l.status = 'late' THEN 1 ELSE 0 END) as late_count"),
                DB::raw("SUM(CASE WHEN l.status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw("SUM(CASE WHEN l.status = 'half_day' THEN 1 ELSE 0 END) as half_day_count"),
                DB::raw("SUM(CASE WHEN l.status = 'leave' THEN 1 ELSE 0 END) as leave_count"),
                DB::raw('COUNT(DISTINCT l.employee_id) as employee_count'),
            ])
            ->get();
    }

    /** @return Collection<int, object> */
    public function byLine(int $factoryId, Carbon $from, Carbon $to): Collection
    {
        return DB::table('hrm_attendance_daily_logs as l')
            ->join('hrm_employees as e', 'e.id', '=', 'l.employee_id')
            ->leftJoin('hrm_lines as ln', 'ln.id', '=', 'e.line_id')
            ->where('l.factory_id', $factoryId)
            ->whereBetween('l.attendance_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('e.line_id', 'ln.name')
            ->orderBy('ln.name')
            ->select([
                'e.line_id',
                DB::raw("COALESCE(ln.name, 'Unassigned') as line_name"),
                DB::raw("SUM(CASE WHEN l.status IN ('present','late') THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN l.status = 'late' THEN 1 ELSE 0 END) as late_count"),
                DB::raw("SUM(CASE WHEN l.status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw("SUM(CASE WHEN l.status = 'half_day' THEN 1 ELSE 0 END) as half_day_count"),
                DB::raw('COUNT(DISTINCT l.employee_id) as employee_count'),
            ])
            ->get();
    }

    /** @return Collection<int, object> */
    public function topLateEmployees(int $factoryId, Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return DB::table('hrm_attendance_daily_logs as l')
            ->join('hrm_employees as e', 'e.id', '=', 'l.employee_id')
            ->where('l.factory_id', $factoryId)
            ->whereBetween('l.attendance_date', [$from->toDateString(), $to->toDateString()])
            ->where('l.status', 'late')
            ->groupBy('l.employee_id', 'e.name', 'e.employee_code')
            ->orderByDesc('late_count')
            ->limit($limit)
            ->select([
                'l.employee_id',
                'e.name',
                'e.employee_code',
                DB::raw('COUNT(*) as late_count'),
                DB::raw('SUM(CASE WHEN l.is_late_forgiven = 1 THEN 1 ELSE 0 END) as forgiven_count'),
            ])
            ->get();
    }

    /** @return Collection<int, AttendanceDailyLog> */
    public function employeeCalendar(Employee $employee, Carbon $from, Carbon $to): Collection
    {
        return AttendanceDailyLog::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('attendance_date')
            ->get()
            ->keyBy(fn ($log) => $log->attendance_date->toDateString());
    }
}
