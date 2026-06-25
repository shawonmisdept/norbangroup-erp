<?php

namespace App\Http\Controllers\Admin\Hrm\Rmg;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\PayrollPeriod;
use App\Services\Hrm\RmgExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    use ScopesHrmFactory;

    public function cashListIndex(Request $request)
    {
        $query = PayrollPeriod::query()->orderByDesc('year')->orderByDesc('month');
        $this->scopeToUserFactory($query, $request);

        return view('admin.hrm.rmg.export.cash-list', [
            'periods'   => $query->get(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
        ]);
    }

    public function cashListExport(Request $request, RmgExportService $export)
    {
        $validated = $request->validate([
            'payroll_period_id' => ['required', 'exists:hrm_payroll_periods,id'],
        ]);

        $period = PayrollPeriod::findOrFail($validated['payroll_period_id']);
        $this->authorizeFactoryAccess($request, $period->factory_id);

        return $export->cashListCsv($period);
    }

    public function buyerAuditIndex(Request $request)
    {
        return view('admin.hrm.rmg.export.buyer-audit', [
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id', 'year', 'month']),
            'year'      => (int) $request->input('year', now()->year),
            'month'     => (int) $request->input('month', now()->month),
        ]);
    }

    public function buyerAuditExport(Request $request, RmgExportService $export)
    {
        $validated = $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'year'       => ['required', 'integer', 'min:2020', 'max:2100'],
            'month'      => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        return $export->buyerAuditCsv(
            (int) $validated['factory_id'],
            (int) $validated['year'],
            (int) $validated['month']
        );
    }
}
