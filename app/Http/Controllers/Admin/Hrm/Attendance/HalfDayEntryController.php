<?php

namespace App\Http\Controllers\Admin\Hrm\Attendance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Services\Hrm\EmployeeScheduleService;
use App\Services\Hrm\HalfDayEntryService;
use Illuminate\Http\Request;

class HalfDayEntryController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = \App\Models\Hrm\AttendanceDailyLog::query()
            ->with(['employee.factory'])
            ->where('status', 'half_day')
            ->latest('attendance_date');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        $entries = $query->paginate(20)->withQueryString();

        return view('admin.hrm.attendance.half-day-entry.index', [
            'entries'   => $entries,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id', 'search']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.attendance.half-day-entry.form', [
            'factories'  => $this->factoryOptions($request),
            'types'      => EmployeeScheduleService::HALF_DAY_TYPES,
            'employees'  => Employee::query()
                ->when($request->user()?->factory_id, fn ($q) => $q->where('factory_id', $request->user()->factory_id))
                ->whereIn('status', ['active', 'probation'])
                ->orderBy('employee_code')
                ->get(['id', 'employee_code', 'name', 'factory_id']),
        ]);
    }

    public function store(Request $request, HalfDayEntryService $service)
    {
        $validated = $request->validate([
            'employee_id'         => ['required', 'exists:hrm_employees,id'],
            'attendance_date'     => ['required', 'date'],
            'half_day_type'       => ['required', 'in:first_half,second_half'],
            'half_day_pay_ratio'  => ['nullable', 'numeric', 'min:0.01', 'max:1'],
            'notes'               => ['nullable', 'string', 'max:500'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, $employee->factory_id);

        $service->apply($employee, $validated, $request->user());

        return redirect()
            ->route('admin.hrm.attendance.half-day-entry.index')
            ->with('success', 'Half day entry saved.');
    }

    public function destroy(Request $request, \App\Models\Hrm\AttendanceDailyLog $halfDayEntry, HalfDayEntryService $service)
    {
        abort_unless($halfDayEntry->status === 'half_day' && $halfDayEntry->is_manual_half_day, 404);

        $this->authorizeFactoryAccess($request, $halfDayEntry->factory_id);

        $service->remove($halfDayEntry);

        return redirect()
            ->route('admin.hrm.attendance.half-day-entry.index')
            ->with('success', 'Half day entry removed.');
    }
}
