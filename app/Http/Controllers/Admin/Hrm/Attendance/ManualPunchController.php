<?php

namespace App\Http\Controllers\Admin\Hrm\Attendance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\AttendanceGatePoint;
use App\Models\Hrm\Employee;
use App\Services\Hrm\AttendancePunchService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ManualPunchController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = \App\Models\Hrm\AttendanceRawPunch::query()
            ->with(['employee.factory', 'enteredByUser'])
            ->where('source', 'manual_hr')
            ->latest('punched_at');

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

        return view('admin.hrm.attendance.manual-punch.index', [
            'entries'   => $entries,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id', 'search']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.attendance.manual-punch.form', [
            'factories' => $this->factoryOptions($request),
            'employees' => Employee::query()
                ->whereIn('status', ['active', 'probation'])
                ->when($request->user()?->factory_id, fn ($q, $fid) => $q->where('factory_id', $fid))
                ->orderBy('employee_code')
                ->get(['id', 'employee_code', 'name', 'factory_id']),
        ]);
    }

    public function store(Request $request, AttendancePunchService $punchService)
    {
        $validated = $request->validate([
            'employee_id'      => ['required', 'exists:hrm_employees,id'],
            'attendance_date'  => ['required', 'date', 'before_or_equal:today'],
            'punch_time'       => ['required', 'date_format:H:i'],
            'punch_type'       => ['required', 'in:in,out'],
            'reason'           => ['required', 'string', 'max:500'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeEmployeeFactory($request, $employee);

        $punchedAt = Carbon::parse($validated['attendance_date'] . ' ' . $validated['punch_time']);

        $punchService->recordManual(
            $employee,
            $punchedAt,
            $validated['punch_type'],
            $request->user(),
            $validated['reason']
        );

        return redirect()
            ->route('admin.hrm.attendance.manual-punch.index')
            ->with('success', 'Manual punch saved and attendance updated.');
    }

    public function destroy(Request $request, \App\Models\Hrm\AttendanceRawPunch $manualPunch, AttendancePunchService $punchService)
    {
        abort_unless($manualPunch->source === 'manual_hr', 404);

        $employee = $manualPunch->employee ?? Employee::findOrFail($manualPunch->employee_id);
        $this->authorizeEmployeeFactory($request, $employee);

        $punchedAt = $manualPunch->punched_at;
        $manualPunch->delete();

        $punchService->reprocessDay($employee, $punchedAt);

        return redirect()
            ->route('admin.hrm.attendance.manual-punch.index')
            ->with('success', 'Manual punch removed and attendance recalculated.');
    }

    private function authorizeEmployeeFactory(Request $request, Employee $employee): void
    {
        if ($request->user()?->factory_id && $request->user()->factory_id !== $employee->factory_id) {
            abort(403);
        }
    }
}
