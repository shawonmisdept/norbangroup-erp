<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesPortalEmployee;
use App\Services\Hrm\EmployeeSeparationService;
use Illuminate\Http\Request;

class SeparationController extends Controller
{
    use ResolvesPortalEmployee;

    public function __construct(private EmployeeSeparationService $service) {}

    public function approve(Request $request, int $separation)
    {
        $approver = $this->portalEmployee($request);
        $record = \App\Models\Hrm\EmployeeSeparation::with('approvals')->findOrFail($separation);

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);

        $this->service->approveByEmployee($record, $approver, $validated['notes'] ?? null);

        return redirect()
            ->route('employee.team')
            ->with('success', 'Separation request forwarded to HR.');
    }

    public function reject(Request $request, int $separation)
    {
        $approver = $this->portalEmployee($request);
        $record = \App\Models\Hrm\EmployeeSeparation::findOrFail($separation);

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);

        $this->service->rejectByEmployee($record, $approver, $validated['rejection_reason']);

        return redirect()
            ->route('employee.team')
            ->with('success', 'Separation request rejected.');
    }
}
