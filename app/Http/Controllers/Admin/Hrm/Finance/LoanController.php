<?php

namespace App\Http\Controllers\Admin\Hrm\Finance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\LoanAccount;
use App\Services\Hrm\HrmNotificationService;
use App\Services\Hrm\LoanRecoveryService;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = LoanAccount::query()->with('employee')->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('admin.hrm.finance.loans.index', [
            'loans'     => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id', 'status']),
            'canManage' => $request->user()?->canManageFinanceSubmodule('loans') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.finance.loans.form', [
            'loan'      => new LoanAccount(['loan_type' => 'advance', 'total_installments' => 3, 'status' => 'pending']),
            'factories' => $this->factoryOptions($request),
            'employees' => $this->employeeOptions($request),
            'types'     => LoanAccount::LOAN_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'factory_id'         => ['required', 'exists:factories,id'],
            'employee_id'        => ['required', 'exists:hrm_employees,id'],
            'loan_type'          => ['required', 'in:' . implode(',', array_keys(LoanAccount::LOAN_TYPES))],
            'principal'          => ['required', 'numeric', 'min:1'],
            'emi_amount'         => ['nullable', 'numeric', 'min:1'],
            'total_installments' => ['required', 'integer', 'min:1', 'max:60'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        abort_if($employee->factory_id !== (int) $validated['factory_id'], 422);

        $validated['emi_amount'] = LoanAccount::calculateEmi(
            (float) $validated['principal'],
            (int) $validated['total_installments']
        );

        LoanAccount::create($validated + [
            'balance'           => $validated['principal'],
            'paid_installments' => 0,
            'status'            => 'pending',
        ]);

        return redirect()->route('admin.hrm.finance.loans.index')
            ->with('success', 'Loan application submitted for approval.');
    }

    public function show(Request $request, LoanAccount $loan)
    {
        $this->authorizeFactoryAccess($request, $loan->factory_id);
        $loan->load(['employee', 'installments', 'approver']);

        return view('admin.hrm.finance.loans.show', [
            'loan'      => $loan,
            'canManage' => $request->user()?->canManageFinanceSubmodule('loans') ?? false,
        ]);
    }

    public function statement(Request $request, LoanAccount $loan)
    {
        $this->authorizeFactoryAccess($request, $loan->factory_id);
        $loan->load(['employee.department', 'employee.designation', 'installments', 'approver', 'factory']);

        return view('hrm.finance.loan-statement-print', [
            'loan'      => $loan,
            'employee'  => $loan->employee,
            'backUrl'   => route('admin.hrm.finance.loans.show', $loan),
            'autoPrint' => $request->boolean('download'),
        ]);
    }

    public function approve(Request $request, LoanAccount $loan, LoanRecoveryService $recovery, HrmNotificationService $notifier)
    {
        $this->authorizeFactoryAccess($request, $loan->factory_id);

        if ($loan->status !== 'pending') {
            return back()->with('error', 'Only pending loans can be approved.');
        }

        $loan->update([
            'status'      => 'active',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        foreach ($recovery->buildSchedule($loan->fresh()) as $installment) {
            $installment->save();
        }

        $notifier->advanceDisbursed($loan->fresh(['employee.portalUser']));

        return redirect()->route('admin.hrm.finance.loans.show', $loan)
            ->with('success', 'Loan approved and EMI schedule created.');
    }

    public function settle(Request $request, LoanAccount $loan, LoanRecoveryService $recovery, HrmNotificationService $notifier)
    {
        $this->authorizeFactoryAccess($request, $loan->factory_id);

        $validated = $request->validate([
            'settlement_amount' => ['nullable', 'numeric', 'min:0.01', 'max:' . max(0.01, (float) $loan->balance)],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $amount = isset($validated['settlement_amount'])
                ? (float) $validated['settlement_amount']
                : (float) $loan->balance;

            $recovery->earlySettle($loan, $request->user(), $amount, $validated['notes'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $notifier->loanSettled($loan->fresh(['employee.portalUser']), $amount);

        return redirect()->route('admin.hrm.finance.loans.show', $loan)
            ->with('success', 'Early settlement recorded.');
    }

    public function reject(Request $request, LoanAccount $loan, HrmNotificationService $notifier)
    {
        $this->authorizeFactoryAccess($request, $loan->factory_id);

        if ($loan->status !== 'pending') {
            return back()->with('error', 'Only pending applications can be rejected.');
        }

        $validated = $request->validate([
            'reject_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $validated['reject_reason'] ?? null;
        $noteLine = '[Rejected ' . now()->format('d M Y') . ' by ' . $request->user()->name . ']';
        if ($reason) {
            $noteLine .= ' — ' . $reason;
        }

        $loan->update([
            'status'  => 'rejected',
            'balance' => 0,
            'notes'   => trim(($loan->notes ? $loan->notes . "\n" : '') . $noteLine),
        ]);

        $notifier->loanRejected($loan->fresh(['employee.portalUser']), $reason);

        return redirect()->route('admin.hrm.finance.loans.index')
            ->with('success', 'Loan application rejected.');
    }

    private function employeeOptions(Request $request): array
    {
        $query = Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }
}
