<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrm\LoanAccount;
use App\Services\Hrm\HrmNotificationService;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user('employee')->employee;

        $loans = LoanAccount::query()
            ->where('employee_id', $employee->id)
            ->with(['installments' => fn ($q) => $q->orderBy('installment_no')])
            ->latest('id')
            ->get();

        $activeLoan = $loans->first(fn ($loan) => $loan->status === 'active' && (float) $loan->balance > 0);
        $pendingLoan = $loans->first(fn ($loan) => $loan->status === 'pending');
        $canApply = ! $activeLoan && ! $pendingLoan;

        return view('employee.loans.index', compact('employee', 'loans', 'activeLoan', 'pendingLoan', 'canApply'));
    }

    public function create(Request $request)
    {
        $employee = $request->user('employee')->employee;

        if ($this->hasOpenApplication($employee->id)) {
            return redirect()->route('employee.loans')
                ->with('error', 'You already have a pending or active loan application.');
        }

        return view('employee.loans.apply', [
            'employee' => $employee,
            'types'    => LoanAccount::LOAN_TYPES,
            'loan'     => new LoanAccount([
                'loan_type'          => 'advance',
                'total_installments' => 3,
            ]),
        ]);
    }

    public function store(Request $request, HrmNotificationService $notifier)
    {
        $employee = $request->user('employee')->employee;

        if ($this->hasOpenApplication($employee->id)) {
            return back()->with('error', 'You already have a pending or active loan application.');
        }

        $validated = $request->validate([
            'loan_type'          => ['required', 'in:' . implode(',', array_keys(LoanAccount::LOAN_TYPES))],
            'principal'          => ['required', 'numeric', 'min:1'],
            'total_installments' => ['required', 'integer', 'min:1', 'max:60'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        $emi = LoanAccount::calculateEmi(
            (float) $validated['principal'],
            (int) $validated['total_installments']
        );

        $loan = LoanAccount::create([
            'factory_id'         => $employee->factory_id,
            'employee_id'        => $employee->id,
            'loan_type'          => $validated['loan_type'],
            'principal'          => $validated['principal'],
            'balance'            => $validated['principal'],
            'emi_amount'         => $emi,
            'total_installments' => $validated['total_installments'],
            'paid_installments'  => 0,
            'status'             => 'pending',
            'notes'              => trim('[Employee portal application]' . ($validated['notes'] ? "\n" . $validated['notes'] : '')),
        ]);

        $notifier->loanApplicationSubmitted($loan->load('employee'));

        return redirect()->route('employee.loans')
            ->with('success', 'Loan application submitted. HR will review shortly.');
    }

    public function statement(Request $request, LoanAccount $loan)
    {
        $employee = $request->user('employee')->employee;

        if ($loan->employee_id !== $employee->id) {
            abort(403);
        }

        $loan->load(['installments', 'approver', 'factory']);

        return view('hrm.finance.loan-statement-print', [
            'loan'      => $loan,
            'employee'  => $employee,
            'backUrl'   => route('employee.loans'),
            'autoPrint' => $request->boolean('download'),
        ]);
    }

    private function hasOpenApplication(int $employeeId): bool
    {
        return LoanAccount::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'active'])
            ->exists();
    }
}
