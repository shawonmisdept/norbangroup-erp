<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrm\AttendanceDailyLog;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user('employee')->employee;

        $logs = AttendanceDailyLog::query()
            ->where('employee_id', $employee->id)
            ->latest('attendance_date')
            ->paginate(20);

        $summary = [
            'present'  => AttendanceDailyLog::where('employee_id', $employee->id)->where('status', 'present')->whereMonth('attendance_date', now()->month)->count(),
            'late'     => AttendanceDailyLog::where('employee_id', $employee->id)->where('status', 'late')->whereMonth('attendance_date', now()->month)->count(),
            'absent'   => AttendanceDailyLog::where('employee_id', $employee->id)->where('status', 'absent')->whereMonth('attendance_date', now()->month)->count(),
            'half_day' => AttendanceDailyLog::where('employee_id', $employee->id)->where('status', 'half_day')->whereMonth('attendance_date', now()->month)->count(),
        ];

        return view('employee.attendance', compact('employee', 'logs', 'summary'));
    }
}
