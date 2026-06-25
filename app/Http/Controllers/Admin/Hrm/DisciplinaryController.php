<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\DisciplinaryRecord;
use App\Models\Hrm\Employee;
use App\Services\Hrm\DisciplinaryService;
use Illuminate\Http\Request;

class DisciplinaryController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private DisciplinaryService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = DisciplinaryRecord::query()
            ->with(['employee', 'recorder'])
            ->latest('incident_date');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        $statsQuery = DisciplinaryRecord::query();
        $this->scopeToUserFactory($statsQuery, $request);

        return view('admin.hrm.discipline.index', [
            'records'      => $query->paginate(20)->withQueryString(),
            'stats'        => [
                'open' => (clone $statsQuery)->where('status', 'open')->count(),
            ],
            'factories'    => $this->factoryOptions($request),
            'actionTypes'  => config('hrm.disciplinary_types', []),
            'statuses'     => DisciplinaryRecord::STATUSES,
            'filters'      => $request->only(['factory_id', 'status', 'action_type', 'search']),
            'canManage'    => $request->user()?->hasPermission('hrm.employees.discipline.manage') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        return view('admin.hrm.discipline.form', [
            'record'           => new DisciplinaryRecord([
                'incident_date' => now()->toDateString(),
                'action_type'   => 'written_warning',
            ]),
            'employees'        => $this->eligibleEmployeeOptions($request, $request->integer('employee_id') ?: null),
            'actionTypes'      => config('hrm.disciplinary_types', []),
            'selectedEmployee' => $request->integer('employee_id') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'employee_id'     => ['required', 'exists:hrm_employees,id'],
            'action_type'     => ['required', 'in:' . implode(',', array_keys(config('hrm.disciplinary_types', [])))],
            'incident_date'   => ['required', 'date'],
            'description'     => ['required', 'string', 'max:5000'],
            'action_taken'    => ['nullable', 'string', 'max:5000'],
            'suspension_from' => ['nullable', 'date', 'required_if:action_type,suspension'],
            'suspension_to'   => ['nullable', 'date', 'after_or_equal:suspension_from'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, $employee->factory_id);

        $record = $this->service->record($employee, $validated, $request->user());

        return redirect()->route('admin.hrm.discipline.show', $record)
            ->with('success', 'Disciplinary record saved.');
    }

    public function show(Request $request, DisciplinaryRecord $discipline)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $discipline->factory_id);

        $discipline->load(['employee.factory', 'employee.department', 'recorder']);

        return view('admin.hrm.discipline.show', [
            'record'    => $discipline,
            'canManage' => $request->user()?->hasPermission('hrm.employees.discipline.manage') ?? false,
        ]);
    }

    public function close(Request $request, DisciplinaryRecord $discipline)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $discipline->factory_id);

        if ($discipline->status !== 'open') {
            return back()->with('error', 'This record is already closed.');
        }

        $this->service->close($discipline);

        return back()->with('success', 'Disciplinary record closed.');
    }

    /** @return array<int, string> */
    private function eligibleEmployeeOptions(Request $request, ?int $includeId = null): array
    {
        $query = Employee::query()->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        $query->where(function ($q) use ($includeId) {
            $q->whereIn('status', ['active', 'probation', 'suspended']);
            if ($includeId) {
                $q->orWhere('id', $includeId);
            }
        });

        return $query->get(['id', 'employee_code', 'name'])
            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->employee_code . ' — ' . $e->name])
            ->all();
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.discipline.view')) {
            abort(403, 'You do not have permission to view disciplinary records.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.discipline.manage')) {
            abort(403, 'You do not have permission to manage disciplinary records.');
        }
    }
}
