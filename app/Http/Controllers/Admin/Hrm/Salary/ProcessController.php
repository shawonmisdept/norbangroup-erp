<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Jobs\Hrm\ProcessPayrollJob;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Services\Hrm\PayrollProcessor;
use Illuminate\Http\Request;

class ProcessController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = PayrollPeriod::query()
            ->with(['factory', 'attendancePeriod', 'calculatedByUser'])
            ->withCount('items')
            ->latest('year')
            ->latest('month');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $periods = $query->paginate(20)->withQueryString();

        return view('admin.hrm.salary.process.index', [
            'periods'   => $periods,
            'factories' => $this->factoryOptions($request),
            'statuses'  => PayrollPeriod::STATUSES,
            'filters'   => $request->only(['factory_id', 'status']),
        ]);
    }

    public function show(Request $request, PayrollPeriod $period)
    {
        $this->authorizeFactoryAccess($request, $period->factory_id);
        $period->load(['factory', 'attendancePeriod', 'calculatedByUser']);

        $itemsQuery = PayrollItem::query()->with('employee')->where('payroll_period_id', $period->id)->orderBy('employee_id');

        if ($request->filled('search')) {
            $search = $request->search;
            $itemsQuery->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        $items = $itemsQuery->paginate(25)->withQueryString();
        $totals = PayrollItem::query()
            ->where('payroll_period_id', $period->id)
            ->selectRaw('COUNT(*) as headcount, SUM(gross_pay) as gross_total, SUM(net_pay) as net_total, SUM(ot_amount) as ot_total')
            ->first();

        return view('admin.hrm.salary.process.show', compact('period', 'items', 'totals') + ['filters' => $request->only(['search'])]);
    }

    public function payslip(Request $request, PayrollPeriod $period, PayrollItem $item)
    {
        $this->authorizeFactoryAccess($request, $period->factory_id);

        if ($item->payroll_period_id !== $period->id) {
            abort(404);
        }

        $item->load([
            'employee.department',
            'employee.designation',
            'employee.salaryStructure.salaryGrade',
            'employee.salaryStructure.salaryBank',
            'salaryBank',
            'period.factory',
        ]);

        return view('admin.hrm.salary.process.payslip-show', [
            'period'  => $period,
            'payslip' => $item,
            'employee'=> $item->employee,
        ]);
    }

    public function payslipPrint(Request $request, PayrollPeriod $period, PayrollItem $item)
    {
        $this->authorizeFactoryAccess($request, $period->factory_id);

        if ($item->payroll_period_id !== $period->id) {
            abort(404);
        }

        $item->load([
            'employee.department',
            'employee.designation',
            'employee.salaryStructure.salaryGrade',
            'employee.salaryStructure.salaryBank',
            'salaryBank',
            'period.factory',
        ]);

        return view('hrm.payslip.print', [
            'period'    => $period,
            'payslip'   => $item,
            'employee'  => $item->employee,
            'backUrl'   => route('admin.hrm.salary.process.payslip', [$period, $item]),
            'autoPrint' => $request->boolean('download'),
        ]);
    }

    public function run(Request $request, PayrollProcessor $processor)
    {
        $validated = $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'year'       => ['required', 'integer', 'min:2020', 'max:2100'],
            'month'      => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $period = PayrollPeriod::getOrCreateForMonth((int) $validated['factory_id'], (int) $validated['year'], (int) $validated['month']);

        if ($period->isFrozen()) {
            return redirect()->back()->withErrors(['period' => 'This period is already closed.'])->withInput();
        }

        try {
            if (config('queue.default') === 'sync') {
                $run = $processor->calculatePeriod($period, $request->user());

                return redirect()
                    ->route('admin.hrm.salary.process.show', $period)
                    ->with('success', "Salary processed for {$period->periodLabel()}. {$run->employee_count} employee(s).");
            }

            ProcessPayrollJob::dispatch($period->id, $request->user()->id);

            return redirect()
                ->route('admin.hrm.salary.process.index')
                ->with('success', "Payroll queued for {$period->periodLabel()}. Refresh shortly to see results.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    }
}
