<?php

namespace App\Http\Controllers\Admin\Hrm\Leave;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\LeaveBalance;
use App\Models\Hrm\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OpeningBalanceController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $query = LeaveBalance::query()
            ->with(['employee', 'leaveType'])
            ->where('year', $year)
            ->orderBy('employee_id');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        $balances = $query->paginate(25)->withQueryString();

        return view('admin.hrm.leave.opening-balances.index', [
            'balances'   => $balances,
            'factories'  => $this->factoryOptions($request),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'year'       => $year,
            'filters'    => $request->only(['factory_id', 'leave_type_id', 'search']),
            'canManage'  => $request->user()?->canManageLeaveSubmodule('opening-balances') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $employees = Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->when($request->user()?->factory_id, fn ($q) => $q->where('factory_id', $request->user()->factory_id))
            ->orderBy('name')
            ->get(['id', 'factory_id', 'employee_code', 'name']);

        return view('admin.hrm.leave.opening-balances.form', [
            'balance'    => new LeaveBalance(['year' => now()->year, 'entitled_days' => 0]),
            'employees'  => $employees,
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'    => ['required', 'exists:hrm_employees,id'],
            'leave_type_id'  => ['required', 'exists:hrm_leave_types,id'],
            'year'           => ['required', 'integer', 'min:2020', 'max:2100'],
            'entitled_days'  => ['required', 'numeric', 'min:0', 'max:365'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, $employee->factory_id);

        LeaveBalance::updateOrCreate(
            [
                'employee_id'   => $employee->id,
                'leave_type_id' => $validated['leave_type_id'],
                'year'          => $validated['year'],
            ],
            [
                'factory_id'    => $employee->factory_id,
                'entitled_days' => $validated['entitled_days'],
            ]
        );

        return redirect()->route('admin.hrm.leave.opening-balances.index', ['year' => $validated['year']])
            ->with('success', 'Opening balance saved.');
    }

    public function edit(Request $request, LeaveBalance $openingBalance)
    {
        $this->authorizeFactoryAccess($request, $openingBalance->factory_id);
        $openingBalance->load(['employee', 'leaveType']);

        return view('admin.hrm.leave.opening-balances.form', [
            'balance'    => $openingBalance,
            'employees'  => collect([$openingBalance->employee]),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, LeaveBalance $openingBalance)
    {
        $this->authorizeFactoryAccess($request, $openingBalance->factory_id);

        $validated = $request->validate([
            'entitled_days' => ['required', 'numeric', 'min:0', 'max:365'],
        ]);

        $openingBalance->update($validated);

        return redirect()->route('admin.hrm.leave.opening-balances.index', ['year' => $openingBalance->year])
            ->with('success', 'Opening balance updated.');
    }
}
