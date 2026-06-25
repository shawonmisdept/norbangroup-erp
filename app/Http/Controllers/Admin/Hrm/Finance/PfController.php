<?php

namespace App\Http\Controllers\Admin\Hrm\Finance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\PfAccount;
use App\Models\Hrm\PfContribution;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PfController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = PfAccount::query()->with('employee')->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return view('admin.hrm.finance.pf.index', [
            'accounts'  => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
            'canManage' => $request->user()?->canManageFinanceSubmodule('pf') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.finance.pf.form', [
            'account'   => new PfAccount([
                'employee_rate_pct' => config('hrm.finance.default_pf_employee_rate', 7),
                'employer_rate_pct' => config('hrm.finance.default_pf_employer_rate', 7.5),
                'is_active'         => true,
                'opened_at'         => now()->toDateString(),
            ]),
            'factories' => $this->factoryOptions($request),
            'employees' => $this->employeeOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'factory_id'        => ['required', 'exists:factories,id'],
            'employee_id'       => ['required', 'exists:hrm_employees,id'],
            'employee_rate_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'employer_rate_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'opened_at'         => ['nullable', 'date'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        abort_if($employee->factory_id !== (int) $validated['factory_id'], 422);

        PfAccount::create($validated + ['is_active' => true, 'balance' => 0]);

        return redirect()->route('admin.hrm.finance.pf.index')
            ->with('success', 'PF account opened for employee.');
    }

    public function show(Request $request, PfAccount $account)
    {
        $this->authorizeFactoryAccess($request, $account->factory_id);
        $account->load(['employee.department', 'employee.designation', 'factory']);

        $contributions = $account->contributions()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(24)
            ->withQueryString();

        return view('admin.hrm.finance.pf.show', [
            'account'       => $account,
            'contributions' => $contributions,
            'totals'        => [
                'employee' => round((float) $account->contributions()->sum('employee_amount'), 2),
                'employer' => round((float) $account->contributions()->sum('employer_amount'), 2),
            ],
        ]);
    }

    public function employerReport(Request $request)
    {
        $factoryId = $request->integer('factory_id') ?: $request->user()?->factory_id;
        $year = $request->integer('year') ?: now()->year;
        $month = $request->integer('month') ?: now()->month;

        $rows = collect();

        if ($factoryId) {
            $this->authorizeFactoryAccess($request, (int) $factoryId);

            $rows = PfContribution::query()
                ->with(['account.employee'])
                ->whereHas('account', fn ($q) => $q->where('factory_id', $factoryId))
                ->where('year', $year)
                ->where('month', $month)
                ->orderBy('id')
                ->get()
                ->map(fn (PfContribution $c) => [
                    'employee'       => $c->account?->employee,
                    'base_amount'    => (float) $c->base_amount,
                    'employee_amount'=> (float) $c->employee_amount,
                    'employer_amount'=> (float) $c->employer_amount,
                ]);
        }

        return view('admin.hrm.finance.pf.employer-report', [
            'rows'            => $rows,
            'factories'       => $this->factoryOptions($request),
            'filterFactoryId' => (string) ($factoryId ?? ''),
            'year'            => $year,
            'month'           => $month,
            'totals'          => [
                'base'     => round((float) $rows->sum('base_amount'), 2),
                'employee' => round((float) $rows->sum('employee_amount'), 2),
                'employer' => round((float) $rows->sum('employer_amount'), 2),
            ],
        ]);
    }

    public function exportEmployerReport(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'year'       => ['required', 'integer', 'min:2020', 'max:2100'],
            'month'      => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $filename = sprintf('pf-employer-report-%s-%02d.csv', $validated['year'], $validated['month']);

        return response()->streamDownload(function () use ($validated) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee Code', 'Employee Name', 'Base Amount', 'Employee PF', 'Employer PF', 'Total PF']);

            PfContribution::query()
                ->with(['account.employee'])
                ->whereHas('account', fn ($q) => $q->where('factory_id', $validated['factory_id']))
                ->where('year', $validated['year'])
                ->where('month', $validated['month'])
                ->orderBy('id')
                ->each(function (PfContribution $c) use ($handle) {
                    $emp = $c->account?->employee;
                    fputcsv($handle, [
                        $emp?->employee_code ?? '',
                        $emp?->name ?? '',
                        number_format((float) $c->base_amount, 2, '.', ''),
                        number_format((float) $c->employee_amount, 2, '.', ''),
                        number_format((float) $c->employer_amount, 2, '.', ''),
                        number_format((float) $c->employee_amount + (float) $c->employer_amount, 2, '.', ''),
                    ]);
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function employeeOptions(Request $request): array
    {
        $query = Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->whereDoesntHave('pfAccount')
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }
}
