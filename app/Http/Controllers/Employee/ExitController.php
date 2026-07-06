<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesPortalEmployee;
use App\Models\Hrm\ContractRenewal;
use App\Models\Hrm\FinalSettlement;
use Illuminate\Http\Request;

class ExitController extends Controller
{
    use ResolvesPortalEmployee;

    public function index(Request $request)
    {
        $employee = $this->portalEmployee($request)->load([
            'latestSeparation.approvals',
            'finalSettlement',
            'issuedLetters' => fn ($q) => $q->whereNull('voided_at')->latest('issued_at'),
        ]);

        $exitClearance = $employee->latestSeparation?->exit_clearance
            ?? \App\Models\Hrm\EmployeeSeparation::defaultExitClearance();

        $downloadableLetters = $employee->issuedLetters
            ->whereIn('letter_type', ['experience', 'relieving']);

        return view('employee.exit.index', [
            'employee'            => $employee,
            'separation'          => $employee->latestSeparation,
            'exitClearance'       => $exitClearance,
            'exitDepartments'     => config('hrm.exit_clearance_departments', []),
            'settlement'          => $employee->finalSettlement,
            'downloadableLetters' => $downloadableLetters,
            'allLetters'          => $employee->issuedLetters->take(10),
        ]);
    }

    public function settlement(Request $request)
    {
        $employee = $this->portalEmployee($request);
        $settlement = $this->resolveSettlement($employee);

        $settlement->load(['factory']);

        return view('employee.exit.settlement', compact('employee', 'settlement'));
    }

    public function settlementPrint(Request $request)
    {
        $employee = $this->portalEmployee($request);
        $settlement = $this->resolveSettlement($employee);

        return view('hrm.finance.final-settlement-print', [
            'employee'   => $employee,
            'settlement' => $settlement,
            'backUrl'    => route('employee.exit.settlement'),
            'autoPrint'  => $request->boolean('download'),
        ]);
    }

    public function contracts(Request $request)
    {
        $employee = $this->portalEmployee($request)->load('pendingContractRenewal');

        $renewals = ContractRenewal::query()
            ->where('employee_id', $employee->id)
            ->latest('id')
            ->paginate(10);

        return view('employee.exit.contracts', compact('employee', 'renewals'));
    }

    private function resolveSettlement(\App\Models\Hrm\Employee $employee): FinalSettlement
    {
        $settlement = $employee->finalSettlement;

        if (! $settlement || ! in_array($settlement->status, ['calculated', 'approved', 'paid'], true)) {
            abort(404);
        }

        return $settlement;
    }
}
