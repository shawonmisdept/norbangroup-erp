<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeeSeparation;
use App\Services\Hrm\EmployeeSeparationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeparationController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private EmployeeSeparationService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = EmployeeSeparation::query()
            ->with(['employee.factory', 'approvals'])
            ->latest('applied_at');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('separation_type')) {
            $query->where('separation_type', $request->separation_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        $statsQuery = EmployeeSeparation::query();
        $this->scopeToUserFactory($statsQuery, $request);

        return view('admin.hrm.separations.index', [
            'separations'     => $query->paginate(20)->withQueryString(),
            'stats'           => [
                'pending_hr'        => (clone $statsQuery)->where('status', 'pending')->where('current_approval_step', EmployeeSeparationService::STEP_HR)->count(),
                'pending_reporting' => (clone $statsQuery)->where('status', 'pending')->where('current_approval_step', EmployeeSeparationService::STEP_REPORTING)->count(),
            ],
            'factories'       => $this->factoryOptions($request),
            'statuses'        => EmployeeSeparation::STATUSES,
            'separationTypes' => $this->service->adminSeparationTypes(),
            'filters'         => $request->only(['factory_id', 'status', 'separation_type', 'search']),
            'canManage'       => $request->user()?->hasPermission('hrm.employees.separation.manage') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        $selectedEmployeeId = $request->integer('employee_id') ?: null;

        return view('admin.hrm.separations.form', [
            'separation'       => new EmployeeSeparation([
                'application_date' => now()->toDateString(),
                'last_working_day' => now()->addDays(30)->toDateString(),
                'separation_type'  => 'resigned',
            ]),
            'employees'        => $this->eligibleEmployeeOptions($request, $selectedEmployeeId),
            'separationTypes'  => $this->service->adminSeparationTypes(),
            'selectedEmployee' => $selectedEmployeeId,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'employee_id'        => ['required', 'exists:hrm_employees,id'],
            'separation_type'    => ['required', 'in:' . implode(',', array_keys($this->service->adminSeparationTypes()))],
            'application_date'   => ['required', 'date'],
            'last_working_day'   => ['required', 'date', 'after_or_equal:application_date'],
            'notice_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'reason'             => ['nullable', 'string', 'max:5000'],
            'remarks'            => ['nullable', 'string', 'max:5000'],
            'attachment'         => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, $employee->factory_id);

        $separation = $this->service->submit(
            $employee,
            $validated,
            'admin',
            $request->user(),
            $request->file('attachment')
        );

        return redirect()->route('admin.hrm.separations.show', $separation)
            ->with('success', 'Separation request submitted.');
    }

    public function show(Request $request, EmployeeSeparation $separation)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $separation->factory_id);

        $separation->load([
            'employee.factory', 'employee.reportingTo', 'approvals.actedByUser',
            'approvals.actedByEmployee', 'approvals.approverEmployee',
            'initiatedByUser', 'approvedByUser', 'rejectedByUser',
        ]);

        return view('admin.hrm.separations.show', [
            'separation' => $separation,
            'canManage'  => $request->user()?->hasPermission('hrm.employees.separation.manage') ?? false,
            'canApprove' => $request->user()?->hasPermission('hrm.employees.separation.approve') ?? false,
        ]);
    }

    public function approve(Request $request, EmployeeSeparation $separation)
    {
        $this->ensureCanApprove($request);
        $this->authorizeFactoryAccess($request, $separation->factory_id);

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);

        $this->service->approve($separation, $request->user(), $validated['notes'] ?? null);

        return back()->with('success', 'Separation approved. Employee status updated.');
    }

    public function reject(Request $request, EmployeeSeparation $separation)
    {
        $this->ensureCanApprove($request);
        $this->authorizeFactoryAccess($request, $separation->factory_id);

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);

        $this->service->reject($separation, $request->user(), $validated['rejection_reason']);

        return back()->with('success', 'Separation request rejected.');
    }

    public function saveExitData(Request $request, EmployeeSeparation $separation)
    {
        $this->ensureCanApprove($request);
        $this->authorizeFactoryAccess($request, $separation->factory_id);

        $departments = array_keys(config('hrm.exit_clearance_departments', []));
        $rules = ['exit_interview_notes' => ['nullable', 'string', 'max:10000']];
        foreach ($departments as $dept) {
            $rules["exit_clearance.{$dept}"] = ['nullable', 'boolean'];
        }

        $validated = $request->validate($rules);

        $this->service->saveExitData($separation, $validated);

        return back()->with('success', 'Exit clearance and interview notes saved.');
    }

    public function cancel(Request $request, EmployeeSeparation $separation)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $separation->factory_id);

        $this->service->cancelByAdmin($separation);

        return back()->with('success', 'Separation request cancelled.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureCanView($request);

        $query = EmployeeSeparation::query()
            ->with(['employee'])
            ->latest('applied_at');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        $filename = 'employee-separations-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Code', 'Name', 'Type', 'Status', 'Application Date', 'Last Working Day', 'Applied At']);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->employee?->employee_code,
                        $row->employee?->name,
                        $row->typeLabel(),
                        $row->statusLabel(),
                        $row->application_date->format('Y-m-d'),
                        $row->last_working_day->format('Y-m-d'),
                        $row->applied_at?->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array<int, string> */
    private function eligibleEmployeeOptions(Request $request, ?int $includeId = null): array
    {
        $query = Employee::query()->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        $query->where(function ($q) use ($includeId) {
            $q->where(function ($inner) {
                $inner->whereIn('status', ['active', 'probation', 'suspended'])
                    ->whereDoesntHave('pendingSeparation');
            });

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
        if (! $request->user()?->hasPermission('hrm.employees.separation.view')) {
            abort(403, 'You do not have permission to view separations.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.separation.manage')) {
            abort(403, 'You do not have permission to manage separations.');
        }
    }

    private function ensureCanApprove(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.separation.approve')) {
            abort(403, 'You do not have permission to approve separations.');
        }
    }
}
