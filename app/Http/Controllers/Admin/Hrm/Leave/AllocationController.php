<?php

namespace App\Http\Controllers\Admin\Hrm\Leave;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\LeaveBalance;
use App\Models\Hrm\LeavePolicy;
use App\Services\Hrm\LeaveService;
use Illuminate\Http\Request;

class AllocationController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        return view('admin.hrm.leave.allocation.index', [
            'factories' => $this->factoryOptions($request),
            'year'      => $year,
            'stats'     => [
                'policies'  => LeavePolicy::when($request->user()?->factory_id, fn ($q) => $q->where('factory_id', $request->user()->factory_id))->where('is_active', true)->count(),
                'balances'  => LeaveBalance::when($request->user()?->factory_id, fn ($q) => $q->where('factory_id', $request->user()->factory_id))->where('year', $year)->count(),
                'employees' => Employee::when($request->user()?->factory_id, fn ($q) => $q->where('factory_id', $request->user()->factory_id))->whereIn('status', ['active', 'probation'])->count(),
            ],
        ]);
    }

    public function run(Request $request, LeaveService $leaveService)
    {
        $validated = $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'year'       => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $employees = Employee::query()
            ->where('factory_id', $validated['factory_id'])
            ->whereIn('status', ['active', 'probation'])
            ->get();

        $allocated = 0;

        foreach ($employees as $employee) {
            $leaveService->ensureEmployeeBalances($employee, (int) $validated['year']);
            $allocated++;
        }

        return redirect()->back()->with('success', "Allocation completed for {$allocated} employee(s) in {$validated['year']}.");
    }
}
