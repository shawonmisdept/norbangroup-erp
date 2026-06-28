<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePromotion;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\WorkerCategory;
use App\Services\Hrm\EmployeePromotionService;
use App\Services\Hrm\HrmNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PromotionController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private EmployeePromotionService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = EmployeePromotion::query()
            ->with(['employee', 'fromDesignation', 'toDesignation'])
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        $statsQuery = EmployeePromotion::query()->where('status', 'pending');
        $this->scopeToUserFactory($statsQuery, $request);

        return view('admin.hrm.promotions.index', [
            'promotions'    => $query->paginate(20)->withQueryString(),
            'pendingCount'  => (clone $statsQuery)->count(),
            'factories'     => $this->factoryOptions($request),
            'statuses'      => EmployeePromotion::STATUSES,
            'movementTypes' => $this->service->movementTypes(),
            'filters'       => $request->only(['factory_id', 'status', 'movement_type', 'search']),
            'canManage'     => $request->user()?->hasPermission('hrm.employees.promotion.manage') ?? false,
            'canApprove'    => $request->user()?->hasPermission('hrm.employees.promotion.approve') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        $selectedEmployeeId = $request->integer('employee_id') ?: null;
        $selectedEmployee = $selectedEmployeeId
            ? Employee::with(['designation', 'department', 'workerCategory', 'salaryStructure.salaryGrade'])->find($selectedEmployeeId)
            : null;

        return view('admin.hrm.promotions.form', [
            'promotion'        => new EmployeePromotion([
                'movement_type'  => 'promotion',
                'effective_date' => now()->toDateString(),
            ]),
            'employees'        => $this->eligibleEmployeeOptions($request, $selectedEmployeeId),
            'designations'     => $this->designationOptions(),
            'departments'      => $this->departmentOptions(),
            'workerCategories' => $this->workerCategoryOptions($request),
            'reportingOptions' => $this->reportingOptions($request),
            'salaryGrades'     => $this->salaryGradeOptions($request),
            'movementTypes'    => $this->service->movementTypes(),
            'selectedEmployee' => $selectedEmployee,
        ]);
    }

    public function store(Request $request, HrmNotificationService $notifier)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'employee_id'           => ['required', 'exists:hrm_employees,id'],
            'movement_type'         => ['required', Rule::in(array_keys(EmployeePromotion::MOVEMENT_TYPES))],
            'to_designation_id'     => ['required', 'exists:designations,id'],
            'to_department_id'      => ['nullable', 'exists:departments,id'],
            'to_worker_category_id' => ['nullable', 'exists:hrm_worker_categories,id'],
            'to_reporting_to_id'    => ['nullable', 'exists:hrm_employees,id'],
            'apply_salary_change'   => ['nullable', 'boolean'],
            'to_salary_grade_id'    => ['nullable', 'required_if:apply_salary_change,1', 'exists:hrm_salary_grades,id'],
            'to_gross_salary'       => ['nullable', 'required_if:apply_salary_change,1', 'numeric', 'min:0'],
            'effective_date'        => ['required', 'date'],
            'reason'                => ['nullable', 'string', 'max:5000'],
            'remarks'               => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['apply_salary_change'] = $request->boolean('apply_salary_change');

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, $employee->factory_id);

        $promotion = $this->service->submit($employee, $validated, $request->user());
        $notifier->promotionPending($promotion->fresh(['employee', 'toDesignation']));

        return redirect()->route('admin.hrm.promotions.show', $promotion)
            ->with('success', 'Promotion/demotion request submitted for approval.');
    }

    public function show(Request $request, EmployeePromotion $promotion)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $promotion->factory_id);

        $promotion->load([
            'employee.factory', 'fromDesignation', 'toDesignation',
            'fromDepartment', 'toDepartment', 'fromWorkerCategory', 'toWorkerCategory',
            'fromReportingTo', 'toReportingTo', 'fromSalaryGrade', 'toSalaryGrade',
            'createdByUser', 'approvedByUser', 'rejectedByUser',
        ]);

        return view('admin.hrm.promotions.show', [
            'promotion'  => $promotion,
            'canManage'  => $request->user()?->hasPermission('hrm.employees.promotion.manage') ?? false,
            'canApprove' => $request->user()?->hasPermission('hrm.employees.promotion.approve') ?? false,
        ]);
    }

    public function approve(Request $request, EmployeePromotion $promotion, HrmNotificationService $notifier)
    {
        $this->ensureCanApprove($request);
        $this->authorizeFactoryAccess($request, $promotion->factory_id);

        $this->service->approve($promotion, $request->user());
        $notifier->promotionApproved($promotion->fresh(['employee']));

        return back()->with('success', 'Request approved. Employee record updated.');
    }

    public function reject(Request $request, EmployeePromotion $promotion, HrmNotificationService $notifier)
    {
        $this->ensureCanApprove($request);
        $this->authorizeFactoryAccess($request, $promotion->factory_id);

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);

        $this->service->reject($promotion, $request->user(), $validated['rejection_reason']);
        $notifier->promotionRejected($promotion->fresh(['employee']));

        return back()->with('success', 'Request rejected.');
    }

    public function cancel(Request $request, EmployeePromotion $promotion)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $promotion->factory_id);

        $this->service->cancel($promotion);

        return back()->with('success', 'Request cancelled.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureCanView($request);

        $query = EmployeePromotion::query()->with(['employee', 'fromDesignation', 'toDesignation'])->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        $filename = 'employee-promotions-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Code', 'Name', 'Type', 'From Designation', 'To Designation', 'Status', 'Effective Date']);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->employee?->employee_code,
                        $row->employee?->name,
                        $row->movementTypeLabel(),
                        $row->fromDesignation?->name,
                        $row->toDesignation?->name,
                        $row->statusLabel(),
                        $row->effective_date->format('Y-m-d'),
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
                $inner->whereIn('status', ['active', 'probation'])
                    ->whereDoesntHave('pendingPromotion');
            });

            if ($includeId) {
                $q->orWhere('id', $includeId);
            }
        });

        return $query->get(['id', 'employee_code', 'name'])
            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->employee_code . ' — ' . $e->name])
            ->all();
    }

    /** @return array<int, string> */
    private function designationOptions(): array
    {
        return Designation::query()
            ->with('department.factory')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Designation $designation) => [
                $designation->id => $designation->displayLabel(),
            ])
            ->all();
    }

    /** @return array<int, string> */
    private function departmentOptions(): array
    {
        return Department::query()
            ->with('factory')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Department $department) => [
                $department->id => $department->displayLabel(),
            ])
            ->all();
    }

    /** @return array<int, string> */
    private function workerCategoryOptions(Request $request): array
    {
        $query = WorkerCategory::query()->where('is_active', true)->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }

    /** @return array<int, string> */
    private function reportingOptions(Request $request): array
    {
        $query = Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        return $query->get(['id', 'employee_code', 'name'])
            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->employee_code . ' — ' . $e->name])
            ->all();
    }

    /** @return array<int, string> */
    private function salaryGradeOptions(Request $request): array
    {
        $query = SalaryGrade::query()->where('is_active', true)->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        return $query->get(['id', 'code', 'name'])
            ->mapWithKeys(fn (SalaryGrade $g) => [$g->id => ($g->code ? $g->code . ' — ' : '') . $g->name])
            ->all();
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.promotion.view')) {
            abort(403, 'You do not have permission to view promotions.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.promotion.manage')) {
            abort(403, 'You do not have permission to manage promotions.');
        }
    }

    private function ensureCanApprove(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.promotion.approve')) {
            abort(403, 'You do not have permission to approve promotions.');
        }
    }
}
