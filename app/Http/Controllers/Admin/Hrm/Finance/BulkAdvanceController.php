<?php

namespace App\Http\Controllers\Admin\Hrm\Finance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Hrm\Employee;
use App\Models\Hrm\LoanAccount;
use App\Services\Hrm\BulkAdvanceService;
use Illuminate\Http\Request;

class BulkAdvanceController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $factoryId = $request->integer('factory_id') ?: $request->user()?->factory_id;
        $departmentId = $request->integer('department_id') ?: null;

        $employees = collect();
        $departments = collect();

        if ($factoryId) {
            $this->authorizeFactoryAccess($request, (int) $factoryId);

            $openLoanEmployeeIds = LoanAccount::query()
                ->where('factory_id', $factoryId)
                ->whereIn('status', ['pending', 'active'])
                ->pluck('employee_id');

            $departments = Department::query()
                ->where('factory_id', $factoryId)
                ->orderBy('name')
                ->get(['id', 'name']);

            $employees = Employee::query()
                ->where('factory_id', $factoryId)
                ->whereIn('status', ['active', 'probation'])
                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                ->with(['department', 'salaryStructure'])
                ->orderBy('employee_code')
                ->get()
                ->map(fn (Employee $emp) => [
                    'employee'      => $emp,
                    'gross'         => (float) ($emp->salaryStructure?->gross_salary ?? 0),
                    'has_open_loan' => $openLoanEmployeeIds->contains($emp->id),
                ]);
        }

        return view('admin.hrm.finance.loans.bulk', [
            'factories'       => $this->factoryOptions($request),
            'departments'     => $departments,
            'employees'       => $employees,
            'filterFactoryId' => (string) ($factoryId ?? ''),
            'filterDeptId'    => (string) ($departmentId ?? ''),
            'canManage'       => $request->user()?->canManageFinanceSubmodule('loans') ?? false,
        ]);
    }

    public function store(Request $request, BulkAdvanceService $service)
    {
        $validated = $request->validate([
            'factory_id'         => ['required', 'exists:factories,id'],
            'default_amount'     => ['nullable', 'numeric', 'min:0'],
            'total_installments' => ['required', 'integer', 'min:1', 'max:60'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'auto_approve'       => ['sometimes', 'boolean'],
            'employee_ids'       => ['required', 'array', 'min:1'],
            'employee_ids.*'     => ['integer', 'exists:hrm_employees,id'],
            'amounts'            => ['nullable', 'array'],
            'amounts.*'          => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $amounts = [];
        foreach ($validated['employee_ids'] as $employeeId) {
            $amount = $validated['amounts'][$employeeId] ?? $validated['default_amount'] ?? 0;

            if ((float) $amount > 0) {
                $amounts[(int) $employeeId] = (float) $amount;
            }
        }

        if ($amounts === []) {
            return back()->withInput()->with('error', 'Set a default advance amount or enter amounts for selected employees.');
        }

        $result = $service->disburse(
            (int) $validated['factory_id'],
            $request->user(),
            $amounts,
            (int) $validated['total_installments'],
            (bool) ($validated['auto_approve'] ?? true),
            $validated['notes'] ?? null
        );

        $message = "Created {$result['created']} advance(s).";
        if ($result['approved'] > 0) {
            $message .= " Approved {$result['approved']} with EMI schedule.";
        }
        if ($result['skipped'] > 0) {
            $message .= " Skipped {$result['skipped']}.";
        }

        if ($result['errors'] !== []) {
            return redirect()->route('admin.hrm.finance.loans.bulk', ['factory_id' => $validated['factory_id']])
                ->with('success', $message)
                ->with('error', implode("\n", array_slice($result['errors'], 0, 10)));
        }

        return redirect()->route('admin.hrm.finance.loans.index')
            ->with('success', $message);
    }
}
