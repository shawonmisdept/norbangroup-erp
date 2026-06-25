<?php

namespace App\Http\Controllers\Admin\Hrm\Finance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeeTaxLedger;
use App\Models\Hrm\TaxSlab;
use App\Models\Hrm\TaxYear;
use App\Services\Hrm\TaxCertificateService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaxController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $yearsQuery = TaxYear::query()->with(['factory', 'slabs'])->withCount('slabs')->latest('start_date');
        $this->scopeToUserFactory($yearsQuery, $request);

        if ($request->filled('factory_id')) {
            $yearsQuery->where('factory_id', $request->factory_id);
        }

        $ledgerQuery = EmployeeTaxLedger::query()->with('employee')->latest('year')->latest('month');
        $this->scopeToUserFactory($ledgerQuery, $request);

        if ($request->filled('factory_id')) {
            $ledgerQuery->where('factory_id', $request->factory_id);
        }

        return view('admin.hrm.finance.tax.index', [
            'years'     => $yearsQuery->paginate(10, ['*'], 'years_page')->withQueryString(),
            'ledgers'   => $ledgerQuery->paginate(25, ['*'], 'ledger_page')->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
            'canManage' => $request->user()?->canManageFinanceSubmodule('tax') ?? false,
            'employees' => $this->employeeOptions($request),
            'allYears'  => TaxYear::query()
                ->when($request->filled('factory_id'), fn ($q) => $q->where('factory_id', $request->factory_id))
                ->when(! $request->filled('factory_id'), fn ($q) => $this->scopeToUserFactory($q, $request))
                ->orderByDesc('start_date')
                ->get(['id', 'factory_id', 'label']),
        ]);
    }

    public function certificate(Request $request, TaxCertificateService $service)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:hrm_employees,id'],
            'tax_year_id' => ['required', 'exists:hrm_tax_years,id'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $taxYear = TaxYear::findOrFail($validated['tax_year_id']);

        $this->authorizeFactoryAccess($request, $employee->factory_id);
        abort_if($taxYear->factory_id !== $employee->factory_id, 422);

        $data = $service->build($employee, $taxYear);

        if ($data['ledgers']->isEmpty()) {
            return back()->with('error', 'No TDS ledger entries found for this employee in the selected assessment year.');
        }

        return view('hrm.finance.tax-certificate-print', $data + [
            'backUrl'   => route('admin.hrm.finance.tax.index', ['factory_id' => $employee->factory_id]),
            'autoPrint' => $request->boolean('download'),
        ]);
    }

    private function employeeOptions(Request $request): array
    {
        $query = Employee::query()
            ->whereIn('status', ['active', 'probation', 'resigned', 'terminated'])
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return $query->pluck('name', 'id')->all();
    }

    public function create(Request $request)
    {
        $start = now()->month >= 7
            ? now()->startOfYear()->month(7)
            : now()->subYear()->startOfYear()->month(7);

        return view('admin.hrm.finance.tax.form', [
            'taxYear'   => new TaxYear([
                'label'      => $start->format('Y') . '-' . substr((string) ($start->year + 1), 2),
                'start_date' => $start->toDateString(),
                'end_date'   => $start->copy()->addYear()->subDay()->toDateString(),
                'is_active'  => true,
            ]),
            'factories' => $this->factoryOptions($request),
            'slabs'     => config('hrm.finance.default_tax_slabs', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'factory_id'  => ['required', 'exists:factories,id'],
            'label'       => ['required', 'string', 'max:20'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after:start_date'],
            'is_active'   => ['nullable', 'boolean'],
            'slabs'       => ['required', 'array', 'min:1'],
            'slabs.*.min_income'   => ['required', 'numeric', 'min:0'],
            'slabs.*.max_income'   => ['nullable', 'numeric'],
            'slabs.*.rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        if ($request->boolean('is_active', true)) {
            TaxYear::query()
                ->where('factory_id', $validated['factory_id'])
                ->update(['is_active' => false]);
        }

        $year = TaxYear::create([
            'factory_id' => $validated['factory_id'],
            'label'      => $validated['label'],
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
            'is_active'  => $request->boolean('is_active', true),
        ]);

        foreach ($validated['slabs'] as $i => $slab) {
            TaxSlab::create([
                'tax_year_id'  => $year->id,
                'min_income'   => $slab['min_income'],
                'max_income'   => $slab['max_income'] ?: null,
                'rate_percent' => $slab['rate_percent'],
                'sort_order'   => $i,
            ]);
        }

        return redirect()->route('admin.hrm.finance.tax.index', ['factory_id' => $year->factory_id])
            ->with('success', 'Tax assessment year created with slabs.');
    }

    public function edit(Request $request, TaxYear $taxYear)
    {
        $this->authorizeFactoryAccess($request, $taxYear->factory_id);
        $taxYear->load('slabs');

        $slabs = $taxYear->slabs->map(fn ($s) => [
            'min'  => $s->min_income,
            'max'  => $s->max_income,
            'rate' => $s->rate_percent,
        ])->values()->all();

        if ($slabs === []) {
            $slabs = config('hrm.finance.default_tax_slabs', []);
        }

        return view('admin.hrm.finance.tax.form', [
            'taxYear'   => $taxYear,
            'factories' => $this->factoryOptions($request),
            'slabs'     => $slabs,
        ]);
    }

    public function update(Request $request, TaxYear $taxYear)
    {
        $this->authorizeFactoryAccess($request, $taxYear->factory_id);

        $validated = $request->validate([
            'label'       => ['required', 'string', 'max:20'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after:start_date'],
            'is_active'   => ['nullable', 'boolean'],
            'slabs'       => ['required', 'array', 'min:1'],
            'slabs.*.min_income'   => ['required', 'numeric', 'min:0'],
            'slabs.*.max_income'   => ['nullable', 'numeric'],
            'slabs.*.rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($request->boolean('is_active')) {
            TaxYear::query()
                ->where('factory_id', $taxYear->factory_id)
                ->where('id', '!=', $taxYear->id)
                ->update(['is_active' => false]);
        }

        $taxYear->update([
            'label'      => $validated['label'],
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
            'is_active'  => $request->boolean('is_active'),
        ]);

        $taxYear->slabs()->delete();

        foreach ($validated['slabs'] as $i => $slab) {
            TaxSlab::create([
                'tax_year_id'  => $taxYear->id,
                'min_income'   => $slab['min_income'],
                'max_income'   => $slab['max_income'] ?: null,
                'rate_percent' => $slab['rate_percent'],
                'sort_order'   => $i,
            ]);
        }

        return redirect()->route('admin.hrm.finance.tax.index', ['factory_id' => $taxYear->factory_id])
            ->with('success', 'Tax assessment year updated.');
    }

    public function exportAnnualTds(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'factory_id'  => ['required', 'exists:factories,id'],
            'tax_year_id' => ['required', 'exists:hrm_tax_years,id'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $taxYear = TaxYear::findOrFail($validated['tax_year_id']);
        abort_if($taxYear->factory_id !== (int) $validated['factory_id'], 422);

        $filename = 'annual-tds-' . str_replace('/', '-', $taxYear->label) . '.csv';

        return response()->streamDownload(function () use ($taxYear) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee Code', 'Employee Name', 'Total Taxable Income', 'Total TDS']);

            $aggregates = EmployeeTaxLedger::query()
                ->where('tax_year_id', $taxYear->id)
                ->selectRaw('employee_id, SUM(taxable_income) as total_taxable, SUM(tds_amount) as total_tds')
                ->groupBy('employee_id')
                ->orderBy('employee_id')
                ->get();

            $employees = Employee::query()
                ->whereIn('id', $aggregates->pluck('employee_id'))
                ->get()
                ->keyBy('id');

            foreach ($aggregates as $row) {
                $emp = $employees->get($row->employee_id);
                fputcsv($handle, [
                    $emp?->employee_code ?? '',
                    $emp?->name ?? '',
                    number_format((float) $row->total_taxable, 2, '.', ''),
                    number_format((float) $row->total_tds, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
