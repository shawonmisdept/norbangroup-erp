<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesPortalEmployee;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\LeaveType;
use App\Services\Hrm\LeaveService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    use ResolvesPortalEmployee;

    public function index(Request $request, LeaveService $leaveService)
    {
        $employee = $request->user('employee')->employee;
        $year = (int) now()->year;

        $leaveService->ensureEmployeeBalances($employee, $year);

        $balances = $employee->leaveBalances()
            ->with('leaveType')
            ->where('year', $year)
            ->get()
            ->sortBy(fn ($balance) => $balance->leaveType->name);

        $applications = LeaveApplication::query()
            ->with(['leaveType', 'employee.reportingTo', 'approvals.actedByEmployee', 'approvals.actedByUser'])
            ->where('employee_id', $employee->id)
            ->latest('applied_at')
            ->paginate(15);

        $pendingApprovals = $leaveService->pendingApprovalsForManager($employee);

        return view('employee.leave.index', compact(
            'employee', 'balances', 'applications', 'year', 'pendingApprovals'
        ));
    }

    public function create(Request $request, LeaveService $leaveService)
    {
        $employee = $request->user('employee')->employee->load('reportingTo');
        $year = (int) now()->year;

        $leaveService->ensureEmployeeBalances($employee, $year);

        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();
        $balances = $employee->leaveBalances()
            ->with('leaveType')
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type_id');

        return view('employee.leave.apply', compact('employee', 'leaveTypes', 'balances', 'year'));
    }

    public function store(Request $request, LeaveService $leaveService)
    {
        $employee = $request->user('employee')->employee;

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:hrm_leave_types,id'],
            'start_date'    => ['required', 'date', 'after_or_equal:today'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['required', 'string', 'max:1000'],
            'attachment'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $leaveService->apply($employee, $validated, $request->file('attachment'));

        return redirect()
            ->route('employee.leave')
            ->with('success', 'Leave application submitted to your reporting person.');
    }

    public function approve(Request $request, LeaveApplication $application, LeaveService $leaveService)
    {
        $employee = $this->portalEmployee($request);

        if ($application->status !== 'pending' || (int) $application->current_approval_step !== LeaveService::STEP_REPORTING) {
            abort(403);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $leaveService->approveByEmployee($application, $employee, $validated['notes'] ?? null);

        return redirect()
            ->to($this->approvalReturnUrl($request))
            ->with('success', 'Leave application forwarded to HR.');
    }

    public function reject(Request $request, LeaveApplication $application, LeaveService $leaveService)
    {
        $employee = $this->portalEmployee($request);

        if ($application->status !== 'pending' || (int) $application->current_approval_step !== LeaveService::STEP_REPORTING) {
            abort(403);
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $leaveService->rejectByEmployee($application, $employee, $validated['rejection_reason']);

        return redirect()
            ->to($this->approvalReturnUrl($request))
            ->with('success', 'Leave application rejected.');
    }

    public function cancel(Request $request, LeaveApplication $application, LeaveService $leaveService)
    {
        $employee = $this->portalEmployee($request);

        if ((int) $application->employee_id !== (int) $employee->id) {
            abort(403);
        }

        $leaveService->cancel($application, $employee);

        return redirect()
            ->route('employee.leave')
            ->with('success', 'Leave application cancelled.');
    }

    private function approvalReturnUrl(Request $request): string
    {
        if ($request->routeIs('employee.team.leave.*')) {
            return route('employee.team');
        }

        return route('employee.leave');
    }
}
