<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesPortalEmployee;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\LoanAccount;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PfAccount;
use App\Services\Hrm\EmployeeCheckInStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ResolvesPortalEmployee;

    public function __invoke(Request $request, EmployeeCheckInStatusService $checkInStatusService)
    {
        $portalUser = Auth::guard('employee')->user();
        $employee = $this->portalEmployee($request)->load(['factory', 'line', 'shift', 'workerCategory']);

        $checkInStatus = $checkInStatusService->forEmployee($employee);

        $month = now()->month;
        $year = now()->year;

        $attendanceSummary = [
            'present'  => AttendanceDailyLog::where('employee_id', $employee->id)->where('status', 'present')->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->count(),
            'late'     => AttendanceDailyLog::where('employee_id', $employee->id)->where('status', 'late')->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->count(),
            'absent'   => AttendanceDailyLog::where('employee_id', $employee->id)->where('status', 'absent')->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->count(),
        ];

        $pendingLeave = LeaveApplication::where('employee_id', $employee->id)->where('status', 'pending')->count();

        $latestPayslip = PayrollItem::query()
            ->with('period')
            ->where('employee_id', $employee->id)
            ->whereHas('period', fn ($q) => $q->whereIn('status', ['calculated', 'frozen']))
            ->latest('id')
            ->first();

        $recentLogs = AttendanceDailyLog::query()
            ->where('employee_id', $employee->id)
            ->latest('attendance_date')
            ->limit(5)
            ->get();

        $activeLoan = LoanAccount::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where('balance', '>', 0)
            ->first();

        $pfAccount = PfAccount::query()
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->first();

        return view('employee.dashboard', compact(
            'employee',
            'portalUser',
            'checkInStatus',
            'attendanceSummary',
            'pendingLeave',
            'latestPayslip',
            'recentLogs',
            'activeLoan',
            'pfAccount',
        ));
    }
}
