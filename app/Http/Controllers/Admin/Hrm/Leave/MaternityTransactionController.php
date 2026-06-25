<?php

namespace App\Http\Controllers\Admin\Hrm\Leave;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\MaternityTransaction;
use App\Services\Hrm\MaternityBenefitService;
use Illuminate\Http\Request;

class MaternityTransactionController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = MaternityTransaction::query()
            ->with(['employee', 'factory'])
            ->latest('start_date')
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return view('admin.hrm.leave.maternity-transactions.index', [
            'transactions' => $query->paginate(25)->withQueryString(),
            'factories'    => $this->factoryOptions($request),
            'filters'      => $request->only(['factory_id']),
            'canManage'    => $request->user()?->canManageLeaveSubmodule('maternity-transactions') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $factoryId = (int) ($request->factory_id ?? $request->user()?->factory_id ?? array_key_first($this->factoryOptions($request)));

        $employees = Employee::query()
            ->where('factory_id', $factoryId)
            ->where('gender', 'female')
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('employee_code')
            ->get(['id', 'employee_code', 'name']);

        return view('admin.hrm.leave.maternity-transactions.form', [
            'transaction' => new MaternityTransaction(['status' => 'pending']),
            'factories'   => $this->factoryOptions($request),
            'employees'   => $employees,
            'factoryId'   => $factoryId,
        ]);
    }

    public function store(Request $request, MaternityBenefitService $service)
    {
        $validated = $request->validate([
            'employee_id'            => ['required', 'exists:hrm_employees,id'],
            'expected_delivery_date' => ['nullable', 'date'],
            'start_date'             => ['required', 'date'],
            'end_date'               => ['required', 'date', 'after_or_equal:start_date'],
            'paid_weeks'             => ['nullable', 'integer', 'min:0', 'max:52'],
            'unpaid_weeks'           => ['nullable', 'integer', 'min:0', 'max:52'],
            'notes'                  => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, $employee->factory_id);

        $service->create($employee, $validated, $request->user());

        return redirect()->route('admin.hrm.leave.maternity-transactions.index')
            ->with('success', 'Maternity benefit recorded and leave created.');
    }

    public function show(Request $request, MaternityTransaction $maternityTransaction)
    {
        $this->authorizeFactoryAccess($request, $maternityTransaction->factory_id);
        $maternityTransaction->load(['employee', 'factory', 'leaveApplication.leaveType', 'creator']);

        return view('admin.hrm.leave.maternity-transactions.show', [
            'transaction' => $maternityTransaction,
        ]);
    }
}
