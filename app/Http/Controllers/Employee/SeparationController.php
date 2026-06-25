<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Hrm\EmployeeSeparationService;
use Illuminate\Http\Request;

class SeparationController extends Controller
{
    public function __construct(private EmployeeSeparationService $service) {}

    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        $employee->load(['pendingSeparation.approvals', 'separations' => fn ($q) => $q->limit(10)]);

        return view('employee.separation.index', [
            'employee'        => $employee,
            'pending'         => $employee->pendingSeparation,
            'history'         => $employee->separations,
            'separationTypes' => $this->service->portalSeparationTypes(),
            'canApply'        => $employee->canInitiateSeparation() && ! $employee->pendingSeparation,
        ]);
    }

    public function store(Request $request)
    {
        $employee = $request->user()->employee;

        $validated = $request->validate([
            'separation_type'  => ['required', 'in:' . implode(',', array_keys($this->service->portalSeparationTypes()))],
            'application_date' => ['required', 'date'],
            'last_working_day' => ['required', 'date', 'after_or_equal:application_date'],
            'notice_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'reason'           => ['required', 'string', 'max:5000'],
            'attachment'       => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $this->service->submit(
            $employee,
            $validated,
            'portal',
            null,
            $request->file('attachment')
        );

        return redirect()->route('employee.separation')
            ->with('success', 'Your resignation request has been submitted.');
    }

    public function cancel(Request $request)
    {
        $employee = $request->user()->employee;
        $pending = $employee->pendingSeparation;

        if (! $pending) {
            return back()->with('error', 'No pending separation request found.');
        }

        $this->service->cancel($pending, $employee);

        return redirect()->route('employee.separation')
            ->with('success', 'Separation request cancelled.');
    }

    public function approve(Request $request, int $separation)
    {
        $approver = $request->user()->employee;
        $record = \App\Models\Hrm\EmployeeSeparation::with('approvals')->findOrFail($separation);

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);

        $this->service->approveByEmployee($record, $approver, $validated['notes'] ?? null);

        return back()->with('success', 'Separation request forwarded to HR.');
    }

    public function reject(Request $request, int $separation)
    {
        $approver = $request->user()->employee;
        $record = \App\Models\Hrm\EmployeeSeparation::findOrFail($separation);

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);

        $this->service->rejectByEmployee($record, $approver, $validated['rejection_reason']);

        return back()->with('success', 'Separation request rejected.');
    }
}
