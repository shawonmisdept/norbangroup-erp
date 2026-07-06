<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\ContractRenewal;
use App\Models\Hrm\Employee;
use App\Services\Hrm\ContractRenewalService;
use Illuminate\Http\Request;

class ContractRenewalController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private ContractRenewalService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = ContractRenewal::query()
            ->with(['employee', 'createdByUser', 'approvedByUser'])
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        return view('admin.hrm.contract-renewals.index', [
            'renewals'  => $query->paginate(20)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'statuses'  => ContractRenewal::STATUSES,
            'filters'   => $request->only(['status', 'search', 'factory_id']),
            'canManage' => $request->user()?->hasPermission('hrm.employees.manage') ?? false,
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $employee->factory_id);

        $validated = $request->validate([
            'new_end_date' => ['required', 'date', 'after:today'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->service->submit($employee, $validated, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Contract renewal submitted for approval.');
    }

    public function approve(Request $request, ContractRenewal $renewal)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $renewal->factory_id);

        try {
            $this->service->approve($renewal, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Contract renewed successfully.');
    }

    public function reject(Request $request, ContractRenewal $renewal)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $renewal->factory_id);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->service->reject($renewal, $request->user(), $validated['rejection_reason']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Contract renewal rejected.');
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.view')) {
            abort(403);
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.manage')) {
            abort(403);
        }
    }
}
