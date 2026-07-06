<?php

namespace App\Http\Controllers\Admin\Hrm\Attendance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\Employee;
use App\Services\Hrm\AttendancePunchService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ManualPunchController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = AttendanceRawPunch::query()
            ->with(['employee.factory', 'enteredByUser'])
            ->where('source', 'manual_hr')
            ->orderByRaw('DATE(punched_at) DESC, punched_at ASC');

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

        $punches = $query->get();
        $groups = $this->groupManualPunches($punches);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        $entries = new LengthAwarePaginator(
            $groups->forPage($page, $perPage)->values(),
            $groups->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.hrm.attendance.manual-punch.index', [
            'entries'   => $entries,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id', 'search']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.attendance.manual-punch.form', [
            'punch'     => new AttendanceRawPunch(['punch_type' => 'in']),
            'employees' => $this->employeeOptions($request),
        ]);
    }

    public function store(Request $request, AttendancePunchService $punchService)
    {
        $validated = $this->validateManualPunch($request);

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

    public function edit(Request $request, AttendanceRawPunch $manualPunch)
    {
        abort_unless($manualPunch->source === 'manual_hr', 404);

        $employee = $manualPunch->employee ?? Employee::findOrFail($manualPunch->employee_id);
        $this->authorizeEmployeeFactory($request, $employee);

        return view('admin.hrm.attendance.manual-punch.form', [
            'punch'     => $manualPunch,
            'employees' => $this->employeeOptions($request),
        ]);
    }

    public function update(Request $request, AttendanceRawPunch $manualPunch, AttendancePunchService $punchService)
    {
        abort_unless($manualPunch->source === 'manual_hr', 404);

        $validated = $this->validateManualPunch($request);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeEmployeeFactory($request, $employee);

        $punchedAt = Carbon::parse($validated['attendance_date'] . ' ' . $validated['punch_time']);

        $punchService->updateManual(
            $manualPunch,
            $employee,
            $punchedAt,
            $validated['punch_type'],
            $validated['reason']
        );

        return redirect()
            ->route('admin.hrm.attendance.manual-punch.index')
            ->with('success', 'Manual punch updated and attendance recalculated.');
    }

    public function destroy(Request $request, AttendanceRawPunch $manualPunch, AttendancePunchService $punchService)
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

    /** @return array<string, mixed> */
    private function validateManualPunch(Request $request): array
    {
        return $request->validate([
            'employee_id'     => ['required', 'exists:hrm_employees,id'],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'punch_time'      => ['required', 'date_format:H:i'],
            'punch_type'      => ['required', 'in:in,out'],
            'reason'          => ['required', 'string', 'max:500'],
        ]);
    }

    private function employeeOptions(Request $request)
    {
        return Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->when($request->user()?->factory_id, fn ($q, $fid) => $q->where('factory_id', $fid))
            ->orderBy('employee_code')
            ->get(['id', 'employee_code', 'name', 'factory_id']);
    }

    private function authorizeEmployeeFactory(Request $request, Employee $employee): void
    {
        if ($request->user()?->factory_id && $request->user()->factory_id !== $employee->factory_id) {
            abort(403);
        }
    }

    /** @return Collection<int, object{employee: ?Employee, date: Carbon, in: ?AttendanceRawPunch, out: ?AttendanceRawPunch}> */
    private function groupManualPunches(Collection $punches): Collection
    {
        return $punches
            ->groupBy(fn (AttendanceRawPunch $punch) => $punch->employee_id . '|' . $punch->punched_at->toDateString())
            ->map(function (Collection $items) {
                $sorted = $items->sortBy('punched_at')->values();

                return (object) [
                    'employee' => $sorted->first()?->employee,
                    'date'     => $sorted->first()->punched_at->copy()->startOfDay(),
                    'in'       => $sorted->firstWhere('punch_type', 'in'),
                    'out'      => $sorted->firstWhere('punch_type', 'out'),
                ];
            })
            ->sortByDesc(fn ($group) => $group->date->timestamp)
            ->values();
    }
}
