<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Services\Hrm\DisbursementSplitService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisbursementController extends Controller
{
    use ScopesHrmFactory;

    public function show(Request $request, PayrollPeriod $period, DisbursementSplitService $disbursement)
    {
        $this->authorizeFactoryAccess($request, $period->factory_id);

        if (! in_array($period->status, ['calculated', 'frozen'], true)) {
            return redirect()
                ->route('admin.hrm.salary.process.show', $period)
                ->withErrors(['period' => 'Disbursement is available after payroll calculation.']);
        }

        $period->load(['factory']);

        $itemsQuery = PayrollItem::query()
            ->with(['employee.line', 'employee.designation', 'salaryBank', 'cashDisbursedByUser'])
            ->where('payroll_period_id', $period->id)
            ->where('net_pay', '>', 0)
            ->orderBy('employee_id');

        if ($request->filled('search')) {
            $search = $request->search;
            $itemsQuery->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        if ($request->filled('cash_status')) {
            if ($request->cash_status === 'pending') {
                $itemsQuery->where('cash_pay_amount', '>', 0)->whereNull('cash_disbursed_at');
            } elseif ($request->cash_status === 'done') {
                $itemsQuery->where(function ($q) {
                    $q->where('cash_pay_amount', '<=', 0)
                        ->orWhereNotNull('cash_disbursed_at');
                });
            }
        }

        $items = $itemsQuery->paginate(30)->withQueryString();

        $totals = PayrollItem::query()
            ->where('payroll_period_id', $period->id)
            ->selectRaw('
                COUNT(*) as headcount,
                SUM(net_pay) as net_total,
                SUM(bank_pay_amount) as bank_total,
                SUM(cash_pay_amount) as cash_total,
                SUM(CASE WHEN cash_pay_amount > 0 AND cash_disbursed_at IS NULL THEN 1 ELSE 0 END) as cash_pending
            ')
            ->first();

        return view('admin.hrm.salary.disbursement.show', [
            'period'       => $period,
            'items'        => $items,
            'totals'       => $totals,
            'pendingCash'  => $disbursement->pendingCashCount($period),
            'canManage'    => $request->user()?->hasPermission('hrm.salary.close.manage') ?? false,
            'filters'      => $request->only(['search', 'cash_status']),
        ]);
    }

    public function updateSplit(Request $request, PayrollPeriod $period, PayrollItem $item, DisbursementSplitService $disbursement)
    {
        $this->authorizeFactoryAccess($request, $period->factory_id);

        if ($item->payroll_period_id !== $period->id) {
            abort(404);
        }

        $validated = $request->validate([
            'bank_pay_amount' => ['required', 'numeric', 'min:0'],
            'cash_pay_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $disbursement->applyOverride(
            $item,
            (float) $validated['bank_pay_amount'],
            (float) $validated['cash_pay_amount']
        );

        return redirect()
            ->back()
            ->with('success', 'Disbursement split updated for ' . ($item->employee?->employee_code ?? 'employee') . '.');
    }

    public function markCashDisbursed(Request $request, PayrollPeriod $period, PayrollItem $item, DisbursementSplitService $disbursement)
    {
        $this->authorizeFactoryAccess($request, $period->factory_id);

        if ($item->payroll_period_id !== $period->id) {
            abort(404);
        }

        $disbursement->markCashDisbursed($item, $request->user());

        return redirect()
            ->back()
            ->with('success', 'Cash disbursed marked for ' . ($item->employee?->employee_code ?? 'employee') . '.');
    }

    public function markAllCashDisbursed(Request $request, PayrollPeriod $period, DisbursementSplitService $disbursement)
    {
        $this->authorizeFactoryAccess($request, $period->factory_id);

        $count = $disbursement->markAllCashDisbursed($period, $request->user());

        if ($count === 0) {
            return redirect()->back()->with('success', 'No pending cash disbursements.');
        }

        return redirect()->back()->with('success', "Marked cash disbursed for {$count} employee(s).");
    }

    public function cashList(Request $request, PayrollPeriod $period): StreamedResponse
    {
        $this->authorizeFactoryAccess($request, $period->factory_id);

        if (! $period->isFrozen()) {
            abort(422, 'Cash list is available only for closed periods.');
        }

        $filename = sprintf('cash-list-%s-%s.csv', $period->factory_id, $period->periodLabel());

        return response()->streamDownload(function () use ($period) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee Code', 'Employee Name', 'Cash Pay', 'Disbursed At']);

            PayrollItem::query()
                ->with('employee')
                ->where('payroll_period_id', $period->id)
                ->where('cash_pay_amount', '>', 0)
                ->orderBy('employee_id')
                ->chunk(100, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        fputcsv($handle, [
                            $item->employee->employee_code,
                            $item->employee->name,
                            number_format((float) $item->cash_pay_amount, 2, '.', ''),
                            $item->cash_disbursed_at?->format('Y-m-d H:i') ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, str_replace(' ', '-', strtolower($filename)), ['Content-Type' => 'text/csv']);
    }
}
