<?php

namespace App\Http\Controllers\Admin\Hrm\Leave;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\LeaveType;
use App\Services\Hrm\LeaveService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = LeaveApplication::query()
            ->with(['employee.factory', 'leaveType', 'approvals'])
            ->latest('applied_at');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('end_date', '<=', $request->date_to);
        }

        if ($request->filled('approval_step')) {
            $query->where('status', 'pending')->where('current_approval_step', $request->approval_step);
        }

        $applications = $query->paginate(20)->withQueryString();

        $statsQuery = LeaveApplication::query();
        $this->scopeToUserFactory($statsQuery, $request);

        return view('admin.hrm.leave.transactions.index', [
            'applications'  => $applications,
            'stats'         => [
                'pending_hr'        => (clone $statsQuery)->where('status', 'pending')->where('current_approval_step', LeaveService::STEP_HR)->count(),
                'pending_reporting' => (clone $statsQuery)->where('status', 'pending')->where('current_approval_step', LeaveService::STEP_REPORTING)->count(),
            ],
            'factories'     => $this->factoryOptions($request),
            'leaveTypes'    => LeaveType::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'statuses'      => LeaveApplication::STATUSES,
            'approvalSteps' => LeaveService::APPROVAL_STEPS,
            'filters'       => $request->only(['factory_id', 'status', 'leave_type_id', 'search', 'date_from', 'date_to', 'approval_step']),
        ]);
    }

    public function show(Request $request, LeaveApplication $transaction)
    {
        $this->authorizeApplicationAccess($request, $transaction);

        $transaction->load([
            'employee.factory', 'employee.reportingTo', 'leaveType', 'approvals.actedByUser',
            'approvals.actedByEmployee', 'approvals.approverEmployee', 'approvedByUser', 'rejectedByUser',
        ]);

        return view('admin.hrm.leave.transactions.show', ['application' => $transaction]);
    }

    public function approve(Request $request, LeaveApplication $transaction, LeaveService $leaveService)
    {
        $this->authorizeApplicationAccess($request, $transaction);

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:500']]);
        $leaveService->approve($transaction, $request->user(), $validated['notes'] ?? null);

        return redirect()->back()->with('success', 'Leave application approved.');
    }

    public function reject(Request $request, LeaveApplication $transaction, LeaveService $leaveService)
    {
        $this->authorizeApplicationAccess($request, $transaction);

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);
        $leaveService->reject($transaction, $request->user(), $validated['rejection_reason']);

        return redirect()->back()->with('success', 'Leave application rejected.');
    }

    private function authorizeApplicationAccess(Request $request, LeaveApplication $application): void
    {
        if ($request->user()?->factory_id && $request->user()->factory_id !== $application->factory_id) {
            abort(403);
        }
    }
}
