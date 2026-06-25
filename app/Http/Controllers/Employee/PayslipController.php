<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrm\PayrollItem;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user('employee')->employee;

        $payslips = PayrollItem::query()
            ->with('period')
            ->where('employee_id', $employee->id)
            ->whereHas('period', fn ($q) => $q->where('status', 'frozen'))
            ->latest('id')
            ->paginate(12);

        return view('employee.payslips', compact('employee', 'payslips'));
    }

    public function show(Request $request, PayrollItem $payslip)
    {
        [$employee, $payslip] = $this->resolvePayslip($request, $payslip);

        return view('employee.payslip-show', compact('employee', 'payslip'));
    }

    public function print(Request $request, PayrollItem $payslip)
    {
        [$employee, $payslip] = $this->resolvePayslip($request, $payslip);

        return view('hrm.payslip.print', [
            'employee'   => $employee,
            'payslip'    => $payslip,
            'backUrl'    => route('employee.payslips.show', $payslip),
            'autoPrint'  => $request->boolean('download'),
        ]);
    }

    /** @return array{0: \App\Models\Hrm\Employee, 1: PayrollItem} */
    private function resolvePayslip(Request $request, PayrollItem $payslip): array
    {
        $employee = $request->user('employee')->employee;

        if ($payslip->employee_id !== $employee->id) {
            abort(403);
        }

        if (! $payslip->period?->isFrozen()) {
            abort(404);
        }

        $payslip->load([
            'period.factory',
            'employee.department',
            'employee.designation',
            'employee.salaryStructure.salaryGrade',
        ]);

        return [$employee, $payslip];
    }
}
