<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesPortalEmployee;
use App\Models\Hrm\FinalSettlement;
use App\Services\Hrm\EmployeeSeparationService;
use App\Services\Hrm\LeaveService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    use ResolvesPortalEmployee;

    public function __construct(
        private LeaveService $leaveService,
        private EmployeeSeparationService $separationService,
    ) {}

    public function index(Request $request)
    {
        $employee = $this->portalEmployee($request);

        if (! $employee->isLineManager()) {
            abort(403, 'You do not have team approval responsibilities.');
        }

        $pendingLeave = $this->leaveService->pendingApprovalsForManager($employee);
        $pendingSeparations = $this->separationService->pendingApprovalsForManager($employee);

        return view('employee.team.index', compact('employee', 'pendingLeave', 'pendingSeparations'));
    }
}
