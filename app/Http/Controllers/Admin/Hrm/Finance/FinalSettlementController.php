<?php

namespace App\Http\Controllers\Admin\Hrm\Finance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\FinalSettlement;
use App\Services\Hrm\FinalSettlementService;
use App\Services\Hrm\HrmNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinalSettlementController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private FinalSettlementService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);
        $query = FinalSettlement::query()
            ->with(['employee', 'factory'])
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('admin.hrm.finance.final-settlement.index', [
            'settlements' => $query->paginate(25)->withQueryString(),
            'factories'   => $this->factoryOptions($request),
            'filters'     => $request->only(['factory_id', 'status']),
            'statuses'    => FinalSettlement::STATUSES,
            'canManage'   => $request->user()?->canManageFinanceSubmodule('final-settlement') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        $selectedEmployeeId = $request->integer('employee_id') ?: null;

        return view('admin.hrm.finance.final-settlement.form', [
            'settlement'         => new FinalSettlement(['last_working_day' => now()->toDateString()]),
            'factories'          => $this->factoryOptions($request),
            'employees'          => $this->separatedEmployeeOptions($request, $selectedEmployeeId),
            'selectedEmployeeId' => $selectedEmployeeId,
            'canManage'          => true,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'employee_id'       => ['required', 'exists:hrm_employees,id'],
            'last_working_day'  => ['required', 'date'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, $employee->factory_id);

        try {
            $settlement = $this->service->createDraft(
                $employee,
                $request->user(),
                Carbon::parse($validated['last_working_day'])
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.hrm.finance.final-settlement.show', $settlement)
            ->with('success', 'Final settlement draft created. Run calculation to build the F&F sheet.');
    }

    public function show(Request $request, FinalSettlement $finalSettlement)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $finalSettlement->factory_id);
        $finalSettlement->load(['employee.department', 'employee.designation', 'gratuitySettlement', 'calculator', 'approver']);

        return view('admin.hrm.finance.final-settlement.show', [
            'settlement' => $finalSettlement,
            'canManage'  => $request->user()?->canManageFinanceSubmodule('final-settlement') ?? false,
        ]);
    }

    public function calculate(Request $request, FinalSettlement $finalSettlement, HrmNotificationService $notifier)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $finalSettlement->factory_id);

        try {
            $this->service->calculate($finalSettlement, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $notifier->finalSettlementCalculated($finalSettlement->fresh(['employee']));

        return back()->with('success', 'Settlement calculated from gratuity, PF, loans, leave & salary data.');
    }

    public function updateAdjustments(Request $request, FinalSettlement $finalSettlement)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $finalSettlement->factory_id);

        $validated = $request->validate([
            'other_earnings'   => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'tax_deduction'    => ['nullable', 'numeric', 'min:0'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->service->updateAdjustments($finalSettlement, $validated);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Adjustments saved.');
    }

    public function updateClearance(Request $request, FinalSettlement $finalSettlement)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $finalSettlement->factory_id);

        $validated = $request->validate([
            'clearance'   => ['required', 'array'],
            'clearance.*' => ['nullable', 'in:0,1,true,false'],
        ]);

        $clearance = collect($validated['clearance'])
            ->map(fn ($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN))
            ->all();

        $this->service->updateClearance($finalSettlement, $clearance);

        return back()->with('success', 'Clearance checklist updated.');
    }

    public function approve(Request $request, FinalSettlement $finalSettlement, HrmNotificationService $notifier)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $finalSettlement->factory_id);

        try {
            $this->service->approve($finalSettlement, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $notifier->finalSettlementApproved($finalSettlement->fresh(['employee.portalUser']));

        return back()->with('success', 'Final settlement approved. Ready for disbursement.');
    }

    public function markPaid(Request $request, FinalSettlement $finalSettlement, HrmNotificationService $notifier)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $finalSettlement->factory_id);

        try {
            $this->service->markPaid($finalSettlement, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $notifier->finalSettlementPaid($finalSettlement->fresh(['employee.portalUser']));

        return back()->with('success', 'Final settlement marked paid. Loans closed & PF account deactivated.');
    }

    public function print(Request $request, FinalSettlement $finalSettlement)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $finalSettlement->factory_id);
        $finalSettlement->load(['employee.department', 'employee.designation', 'factory', 'gratuitySettlement']);

        return view('hrm.finance.final-settlement-print', [
            'settlement' => $finalSettlement,
            'employee'   => $finalSettlement->employee,
            'backUrl'    => route('admin.hrm.finance.final-settlement.show', $finalSettlement),
            'autoPrint'  => $request->boolean('download'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureCanView($request);

        $factoryId = $request->integer('factory_id') ?: $request->user()?->factory_id;

        abort_unless($factoryId, 422, 'Factory is required.');

        $this->authorizeFactoryAccess($request, (int) $factoryId);

        $rows = FinalSettlement::query()
            ->with('employee')
            ->where('factory_id', $factoryId)
            ->orderByDesc('id')
            ->get();

        $filename = 'final-settlements-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Employee Code', 'Employee Name', 'Separation', 'Last Working Day',
                'Net Payable', 'Status', 'Paid At',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->employee?->employee_code ?? '',
                    $row->employee?->name ?? '',
                    ucfirst($row->separation_type),
                    $row->last_working_day->format('Y-m-d'),
                    number_format((float) $row->net_payable, 2, '.', ''),
                    ucfirst($row->status),
                    $row->paid_at?->format('Y-m-d') ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array<int, string> */
    private function separatedEmployeeOptions(Request $request, ?int $includeEmployeeId = null): array
    {
        $query = Employee::query()
            ->whereIn('status', ['resigned', 'terminated'])
            ->where(function ($q) use ($includeEmployeeId) {
                $q->whereDoesntHave('finalSettlement');

                if ($includeEmployeeId) {
                    $q->orWhere('id', $includeEmployeeId);
                }
            })
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        return $query->get(['id', 'employee_code', 'name'])
            ->mapWithKeys(fn (Employee $employee) => [
                $employee->id => trim($employee->employee_code . ' — ' . $employee->name),
            ])
            ->all();
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->canViewFinanceSubmodule('final-settlement')) {
            abort(403, 'You do not have permission to view final settlement.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->canManageFinanceSubmodule('final-settlement')) {
            abort(403, 'You do not have permission to manage final settlement.');
        }
    }
}
