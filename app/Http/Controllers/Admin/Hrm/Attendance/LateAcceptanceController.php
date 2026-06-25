<?php

namespace App\Http\Controllers\Admin\Hrm\Attendance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\LateAcceptanceApplication;
use App\Services\Hrm\LateAcceptanceService;
use Illuminate\Http\Request;

class LateAcceptanceController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = LateAcceptanceApplication::query()
            ->with(['employee.factory', 'approvedByUser'])
            ->latest('applied_at');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }

        $applications = $query->paginate(20)->withQueryString();

        $statsQuery = LateAcceptanceApplication::query();
        $this->scopeToUserFactory($statsQuery, $request);

        return view('admin.hrm.attendance.late-acceptance.index', [
            'applications' => $applications,
            'stats'        => [
                'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            ],
            'factories'    => $this->factoryOptions($request),
            'statuses'     => LateAcceptanceApplication::STATUSES,
            'filters'      => $request->only(['factory_id', 'status', 'search', 'date_from', 'date_to']),
        ]);
    }

    public function show(Request $request, LateAcceptanceApplication $lateAcceptance)
    {
        $this->authorizeApplicationAccess($request, $lateAcceptance);

        $lateAcceptance->load([
            'employee.factory',
            'employee.designation',
            'approvedByUser',
            'rejectedByUser',
        ]);

        return view('admin.hrm.attendance.late-acceptance.show', [
            'application' => $lateAcceptance,
        ]);
    }

    public function approve(Request $request, LateAcceptanceApplication $lateAcceptance, LateAcceptanceService $service)
    {
        $this->authorizeApplicationAccess($request, $lateAcceptance);
        abort_unless($request->user()->hasPermission('hrm.attendance.approve'), 403);

        $service->approve($lateAcceptance, $request->user());

        return redirect()
            ->route('admin.hrm.attendance.late-acceptance.show', $lateAcceptance)
            ->with('success', 'Late acceptance approved.');
    }

    public function reject(Request $request, LateAcceptanceApplication $lateAcceptance, LateAcceptanceService $service)
    {
        $this->authorizeApplicationAccess($request, $lateAcceptance);
        abort_unless($request->user()->hasPermission('hrm.attendance.approve'), 403);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $service->reject($lateAcceptance, $request->user(), $validated['rejection_reason']);

        return redirect()
            ->route('admin.hrm.attendance.late-acceptance.show', $lateAcceptance)
            ->with('success', 'Late acceptance rejected.');
    }

    private function authorizeApplicationAccess(Request $request, LateAcceptanceApplication $application): void
    {
        $this->authorizeFactoryAccess($request, $application->factory_id);
    }
}
