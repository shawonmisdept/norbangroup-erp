<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\LateAcceptanceApplication;
use App\Services\Hrm\LateAcceptanceService;
use Illuminate\Http\Request;

class LateAcceptanceController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user('employee')->employee;
        $employee->loadMissing('salaryStructure');

        if ($employee->salaryStructure?->pay_type === 'wages') {
            abort(403, 'Late acceptance is only available for salaried staff.');
        }

        $applications = LateAcceptanceApplication::query()
            ->where('employee_id', $employee->id)
            ->latest('applied_at')
            ->paginate(15);

        $lateDays = AttendanceDailyLog::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'late')
            ->where('is_late_forgiven', false)
            ->latest('attendance_date')
            ->limit(10)
            ->get();

        return view('employee.late-acceptance.index', compact('employee', 'applications', 'lateDays'));
    }

    public function create(Request $request)
    {
        $employee = $request->user('employee')->employee;
        $employee->loadMissing('salaryStructure');

        if ($employee->salaryStructure?->pay_type === 'wages') {
            abort(403, 'Late acceptance is only available for salaried staff.');
        }

        $lateDays = AttendanceDailyLog::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'late')
            ->where('is_late_forgiven', false)
            ->whereNotIn('attendance_date', LateAcceptanceApplication::query()
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['pending', 'approved'])
                ->pluck('attendance_date'))
            ->latest('attendance_date')
            ->limit(30)
            ->get();

        return view('employee.late-acceptance.apply', compact('employee', 'lateDays'));
    }

    public function store(Request $request, LateAcceptanceService $service)
    {
        $employee = $request->user('employee')->employee;
        $employee->loadMissing('salaryStructure');

        if ($employee->salaryStructure?->pay_type === 'wages') {
            abort(403, 'Late acceptance is only available for salaried staff.');
        }

        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'reason'          => ['required', 'string', 'max:500'],
        ]);

        $service->apply($employee, $validated);

        return redirect()
            ->route('employee.late-acceptance.index')
            ->with('success', 'Late acceptance application submitted.');
    }
}
