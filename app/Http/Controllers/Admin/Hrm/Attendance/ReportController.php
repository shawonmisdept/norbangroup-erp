<?php

namespace App\Http\Controllers\Admin\Hrm\Attendance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Services\Hrm\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request, AttendanceReportService $reports)
    {
        $factories = $this->factoryOptions($request);
        $factoryId = (int) ($request->factory_id ?? array_key_first($factories) ?? 0);
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        if ($factoryId && $request->user()?->factory_id) {
            $this->authorizeFactoryAccess($request, $factoryId);
        }

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $summary = $factoryId
            ? $reports->monthlySummary($factoryId, $from, $to)
            : [];

        return view('admin.hrm.attendance.reports.index', [
            'factories'    => $factories,
            'factoryId'    => $factoryId,
            'year'         => $year,
            'month'        => $month,
            'periodLabel'  => $from->format('F Y'),
            'summary'      => $summary,
            'byDepartment' => $factoryId ? $reports->byDepartment($factoryId, $from, $to) : collect(),
            'byLine'       => $factoryId ? $reports->byLine($factoryId, $from, $to) : collect(),
            'topLate'      => $factoryId ? $reports->topLateEmployees($factoryId, $from, $to) : collect(),
            'filters'      => $request->only(['factory_id', 'year', 'month']),
        ]);
    }

    public function employeeCalendar(Request $request, Employee $employee, AttendanceReportService $reports)
    {
        $this->authorizeFactoryAccess($request, $employee->factory_id);

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $logs = $reports->employeeCalendar($employee, $from, $to);

        $days = [];
        foreach (Carbon::parse($from)->daysUntil($to) as $date) {
            $key = $date->toDateString();
            $days[] = [
                'date' => $date,
                'log'  => $logs->get($key),
            ];
        }

        return view('admin.hrm.attendance.reports.employee-calendar', [
            'employee'    => $employee->load(['department', 'line', 'shift']),
            'days'        => $days,
            'year'        => $year,
            'month'       => $month,
            'periodLabel' => $from->format('F Y'),
        ]);
    }

    public function export(Request $request, AttendanceReportService $reports): StreamedResponse
    {
        $factoryId = (int) $request->input('factory_id');
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $this->authorizeFactoryAccess($request, $factoryId);

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $rows = $reports->byDepartment($factoryId, $from, $to);

        $filename = sprintf('attendance-report-%s-%d-%02d.csv', $factoryId, $year, $month);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Department', 'Employees', 'Present', 'Late', 'Absent', 'Half Day', 'Leave']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->department_name,
                    $row->employee_count,
                    $row->present_count,
                    $row->late_count,
                    $row->absent_count,
                    $row->half_day_count,
                    $row->leave_count,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
