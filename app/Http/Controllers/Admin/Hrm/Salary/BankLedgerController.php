<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\PayrollPeriod;
use App\Services\Hrm\BankLedgerService;
use Illuminate\Http\Request;

class BankLedgerController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request, BankLedgerService $ledger)
    {
        $factories = $this->factoryOptions($request);
        $factoryId = $request->filled('factory_id')
            ? (int) $request->factory_id
            : (count($factories) === 1 ? (int) array_key_first($factories) : null);

        if ($factoryId) {
            $this->authorizeFactoryAccess($request, $factoryId);
        }

        $periods = $ledger->frozenPeriodOptions($factoryId);
        $selectedPeriod = null;

        if ($request->filled('payroll_period_id')) {
            $selectedPeriod = PayrollPeriod::query()->find($request->payroll_period_id);
            if ($selectedPeriod) {
                $this->authorizeFactoryAccess($request, $selectedPeriod->factory_id);
            }
        } elseif ($periods->isNotEmpty()) {
            $selectedPeriod = $periods->first();
        }

        $filters = [
            'factory_id'        => $factoryId,
            'payroll_period_id'   => $selectedPeriod?->id,
            'salary_bank_id'      => $request->input('salary_bank_id'),
            'search'              => $request->input('search'),
        ];

        $summary = collect();
        $totals = ['headcount' => 0, 'bank_total' => 0, 'cash_total' => 0, 'net_total' => 0];
        $items = null;
        $unassignedCount = 0;

        if ($selectedPeriod) {
            $filters['payroll_period_id'] = $selectedPeriod->id;
            $filters['factory_id'] = $selectedPeriod->factory_id;
            $summary = $ledger->summaryRows($filters);
            $totals = $ledger->summaryTotals($summary);
            $items = $ledger->detailPaginator($filters);
            $unassignedCount = $ledger->unassignedCount($filters);
        }

        return view('admin.hrm.salary.bank-ledger.index', [
            'factories'       => $factories,
            'periods'         => $periods,
            'selectedPeriod'  => $selectedPeriod,
            'bankOptions'     => $ledger->bankFilterOptions($factoryId ?? $selectedPeriod?->factory_id),
            'summary'         => $summary,
            'totals'          => $totals,
            'items'           => $items,
            'unassignedCount' => $unassignedCount,
            'filters'         => $request->only(['factory_id', 'payroll_period_id', 'salary_bank_id', 'search']),
        ]);
    }

    public function exportSummary(Request $request, BankLedgerService $ledger)
    {
        $period = $this->resolvePeriod($request);
        $filters = $this->filtersForPeriod($request, $period);

        return $ledger->exportSummaryCsv($filters, $period);
    }

    public function exportDetail(Request $request, BankLedgerService $ledger)
    {
        $period = $this->resolvePeriod($request);
        $filters = $this->filtersForPeriod($request, $period);

        return $ledger->exportDetailCsv($filters, $period);
    }

    private function resolvePeriod(Request $request): PayrollPeriod
    {
        $request->validate([
            'payroll_period_id' => ['required', 'exists:hrm_payroll_periods,id'],
        ]);

        $period = PayrollPeriod::query()->findOrFail($request->payroll_period_id);
        $this->authorizeFactoryAccess($request, $period->factory_id);

        if (! $period->isFrozen()) {
            abort(422, 'Bank ledger is available only for closed (frozen) payroll periods.');
        }

        return $period;
    }

    /** @return array{factory_id: int, payroll_period_id: int, salary_bank_id?: string|null, search?: string|null} */
    private function filtersForPeriod(Request $request, PayrollPeriod $period): array
    {
        return [
            'factory_id'       => $period->factory_id,
            'payroll_period_id'  => $period->id,
            'salary_bank_id'   => $request->input('salary_bank_id'),
            'search'           => $request->input('search'),
        ];
    }
}
