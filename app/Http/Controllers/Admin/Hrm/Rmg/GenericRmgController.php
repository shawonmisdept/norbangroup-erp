<?php

namespace App\Http\Controllers\Admin\Hrm\Rmg;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Hrm\BuyerHoliday;
use App\Models\Hrm\CanteenDeduction;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Line;
use App\Models\Hrm\MedicalVisit;
use App\Models\Hrm\OsdMovement;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\ProductionIncentive;
use App\Models\Hrm\SalaryHold;
use App\Models\Hrm\SubContractWorker;
use App\Models\Hrm\TrainingRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GenericRmgController extends Controller
{
    use ScopesHrmFactory;

    private const GENERIC_KEYS = [
        'osd-movement',
        'canteen',
        'medical',
        'training',
        'sub-contract',
        'buyer-holiday',
        'salary-hold',
        'production-incentive',
    ];

    public function index(Request $request, string $submodule)
    {
        $this->assertGenericSubmodule($submodule);
        $config = config("hrm.rmg_submodules.{$submodule}");

        $query = $this->modelClass($submodule)::query()->latest('id');
        $this->applyRelations($query, $submodule);
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return view('admin.hrm.rmg.generic.index', [
            'submodule' => $submodule,
            'config'    => $config,
            'records'   => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
            'canManage' => $request->user()?->canManageRmgSubmodule($submodule) ?? false,
            'columns'   => $this->indexColumns($submodule),
        ]);
    }

    public function create(Request $request, string $submodule)
    {
        $this->assertGenericSubmodule($submodule);
        $config = config("hrm.rmg_submodules.{$submodule}");

        return view('admin.hrm.rmg.generic.form', [
            'submodule' => $submodule,
            'config'    => $config,
            'record'    => $this->newRecord($submodule),
            'factories' => $this->factoryOptions($request),
            'employees' => $this->employeeOptions($request),
            'lines'     => $this->lineOptions($request),
            'buyers'    => Buyer::orderBy('name')->pluck('name', 'id')->all(),
            'periods'   => $this->payrollPeriodOptions($request),
            'types'     => $this->typeOptions($submodule),
        ]);
    }

    public function store(Request $request, string $submodule)
    {
        $this->assertGenericSubmodule($submodule);
        $validated = $this->validateStore($request, $submodule);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        if (isset($validated['employee_id'])) {
            $employee = Employee::findOrFail($validated['employee_id']);
            abort_if($employee->factory_id !== (int) $validated['factory_id'], 422);
        }

        if ($submodule === 'production-incentive') {
            $validated['total_amount'] = (float) $validated['output_qty'] * (float) $validated['incentive_rate'];
            $validated['status'] = 'draft';
        }

        if ($submodule === 'salary-hold') {
            $validated['status'] = 'active';
        }

        if ($submodule === 'osd-movement') {
            $validated['status'] = 'pending';
        }

        if ($submodule === 'sub-contract') {
            $validated['status'] = $validated['status'] ?? 'active';
        }

        if ($submodule === 'buyer-holiday') {
            $validated['is_active'] = $request->boolean('is_active', true);
        }

        $this->modelClass($submodule)::create($validated + ['created_by' => $request->user()->id]);

        $label = config("hrm.rmg_submodules.{$submodule}.label", ucfirst($submodule));

        return redirect()->route("admin.hrm.rmg.{$submodule}.index")
            ->with('success', $label . ' record saved.');
    }

    public function release(Request $request, SalaryHold $salaryHold)
    {
        $this->authorizeFactoryAccess($request, $salaryHold->factory_id);

        if ($salaryHold->status !== 'active') {
            return back()->with('error', 'Only active salary holds can be released.');
        }

        $salaryHold->update([
            'status'      => 'released',
            'released_by' => $request->user()->id,
            'released_at' => now(),
        ]);

        return redirect()->route('admin.hrm.rmg.salary-hold.index')
            ->with('success', 'Salary hold released.');
    }

    public function approveIncentive(Request $request, ProductionIncentive $productionIncentive)
    {
        $this->authorizeFactoryAccess($request, $productionIncentive->factory_id);

        if ($productionIncentive->status !== 'draft') {
            return back()->with('error', 'Only draft incentives can be approved.');
        }

        $productionIncentive->update([
            'status'      => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.hrm.rmg.production-incentive.index')
            ->with('success', 'Production incentive approved.');
    }

    private function assertGenericSubmodule(string $submodule): void
    {
        if (! in_array($submodule, self::GENERIC_KEYS, true)) {
            abort(404);
        }
    }

    private function modelClass(string $submodule): string
    {
        return match ($submodule) {
            'osd-movement'         => OsdMovement::class,
            'canteen'              => CanteenDeduction::class,
            'medical'              => MedicalVisit::class,
            'training'             => TrainingRecord::class,
            'sub-contract'         => SubContractWorker::class,
            'buyer-holiday'        => BuyerHoliday::class,
            'salary-hold'          => SalaryHold::class,
            'production-incentive' => ProductionIncentive::class,
            default                => abort(404),
        };
    }

    private function applyRelations($query, string $submodule): void
    {
        match ($submodule) {
            'osd-movement', 'canteen', 'medical', 'training', 'salary-hold' => $query->with('employee'),
            'sub-contract', 'production-incentive' => $query->with('line'),
            'buyer-holiday' => $query->with('buyer'),
            default => null,
        };
    }

    private function newRecord(string $submodule): Model
    {
        $defaults = match ($submodule) {
            'osd-movement' => [
                'movement_type' => 'official_duty',
                'start_date'    => now()->toDateString(),
                'end_date'      => now()->toDateString(),
                'status'        => 'pending',
            ],
            'canteen' => [
                'period_year'  => (int) now()->year,
                'period_month' => (int) now()->month,
                'meal_count'   => 0,
                'amount'       => 0,
            ],
            'medical' => ['visit_date' => now()->toDateString(), 'referred' => false],
            'training' => [
                'training_type'  => 'safety',
                'training_date'  => now()->toDateString(),
            ],
            'sub-contract' => [
                'start_date' => now()->toDateString(),
                'status'     => 'active',
            ],
            'buyer-holiday' => [
                'date'       => now()->toDateString(),
                'is_active'  => true,
            ],
            'salary-hold' => [
                'hold_from' => now()->toDateString(),
                'status'    => 'active',
            ],
            'production-incentive' => [
                'period_year'    => (int) now()->year,
                'period_month'   => (int) now()->month,
                'output_qty'     => 0,
                'incentive_rate' => 0,
                'status'         => 'draft',
            ],
            default => [],
        };

        return $this->modelClass($submodule)::make($defaults);
    }

    private function indexColumns(string $submodule): array
    {
        return match ($submodule) {
            'osd-movement' => ['Employee', 'Type', 'Dates', 'Status'],
            'canteen' => ['Employee', 'Period', 'Meals', 'Amount'],
            'medical' => ['Employee', 'Visit Date', 'Complaint', 'Referred'],
            'training' => ['Employee', 'Type', 'Title', 'Training Date'],
            'sub-contract' => ['Agency', 'Name', 'Line', 'Status'],
            'buyer-holiday' => ['Buyer', 'Name', 'Date', 'Active'],
            'salary-hold' => ['Employee', 'From', 'Until', 'Status'],
            'production-incentive' => ['Line', 'Period', 'Output', 'Amount', 'Status'],
            default => ['ID'],
        };
    }

    private function typeOptions(string $submodule): array
    {
        return match ($submodule) {
            'osd-movement' => OsdMovement::TYPES,
            'training'     => TrainingRecord::TYPES,
            default        => [],
        };
    }

    private function validateStore(Request $request, string $submodule): array
    {
        $rules = match ($submodule) {
            'osd-movement' => [
                'factory_id'     => ['required', 'exists:factories,id'],
                'employee_id'    => ['required', 'exists:hrm_employees,id'],
                'movement_type'  => ['required', Rule::in(array_keys(OsdMovement::TYPES))],
                'start_date'     => ['required', 'date'],
                'end_date'       => ['required', 'date', 'after_or_equal:start_date'],
                'destination'    => ['nullable', 'string', 'max:255'],
                'purpose'        => ['nullable', 'string', 'max:1000'],
            ],
            'canteen' => [
                'factory_id'     => ['required', 'exists:factories,id'],
                'employee_id'    => ['required', 'exists:hrm_employees,id'],
                'period_year'    => ['required', 'integer', 'min:2020', 'max:2100'],
                'period_month'   => ['required', 'integer', 'min:1', 'max:12'],
                'meal_count'     => ['required', 'integer', 'min:0'],
                'amount'         => ['required', 'numeric', 'min:0'],
                'notes'          => ['nullable', 'string', 'max:1000'],
            ],
            'medical' => [
                'factory_id'  => ['required', 'exists:factories,id'],
                'employee_id' => ['required', 'exists:hrm_employees,id'],
                'visit_date'  => ['required', 'date'],
                'complaint'   => ['nullable', 'string', 'max:255'],
                'diagnosis'   => ['nullable', 'string', 'max:255'],
                'treatment'   => ['nullable', 'string', 'max:255'],
                'referred'    => ['sometimes', 'boolean'],
                'notes'       => ['nullable', 'string', 'max:1000'],
            ],
            'training' => [
                'factory_id'      => ['required', 'exists:factories,id'],
                'employee_id'     => ['required', 'exists:hrm_employees,id'],
                'training_type'   => ['required', Rule::in(array_keys(TrainingRecord::TYPES))],
                'title'           => ['required', 'string', 'max:255'],
                'provider'        => ['nullable', 'string', 'max:255'],
                'training_date'   => ['required', 'date'],
                'expiry_date'     => ['nullable', 'date', 'after_or_equal:training_date'],
                'certificate_no'  => ['nullable', 'string', 'max:100'],
                'notes'           => ['nullable', 'string', 'max:1000'],
            ],
            'sub-contract' => [
                'factory_id'   => ['required', 'exists:factories,id'],
                'line_id'      => ['nullable', 'exists:hrm_lines,id'],
                'agency_name'  => ['required', 'string', 'max:255'],
                'name'         => ['required', 'string', 'max:255'],
                'phone'        => ['nullable', 'string', 'max:20'],
                'nid_number'   => ['nullable', 'string', 'max:30'],
                'start_date'   => ['required', 'date'],
                'end_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
                'status'       => ['nullable', Rule::in(array_keys(SubContractWorker::STATUSES))],
                'notes'        => ['nullable', 'string', 'max:1000'],
            ],
            'buyer-holiday' => [
                'factory_id'  => ['required', 'exists:factories,id'],
                'buyer_id'    => ['required', 'exists:buyers,id'],
                'name'        => ['required', 'string', 'max:255'],
                'date'        => ['required', 'date'],
                'description' => ['nullable', 'string', 'max:1000'],
                'is_active'   => ['sometimes', 'boolean'],
            ],
            'salary-hold' => [
                'factory_id'        => ['required', 'exists:factories,id'],
                'employee_id'       => ['required', 'exists:hrm_employees,id'],
                'payroll_period_id' => ['nullable', 'exists:hrm_payroll_periods,id'],
                'reason'            => ['required', 'string', 'max:1000'],
                'hold_from'         => ['required', 'date'],
                'hold_until'        => ['nullable', 'date', 'after_or_equal:hold_from'],
            ],
            'production-incentive' => [
                'factory_id'     => ['required', 'exists:factories,id'],
                'line_id'        => ['required', 'exists:hrm_lines,id'],
                'period_year'    => ['required', 'integer', 'min:2020', 'max:2100'],
                'period_month'   => ['required', 'integer', 'min:1', 'max:12'],
                'output_qty'     => ['required', 'integer', 'min:0'],
                'incentive_rate' => ['required', 'numeric', 'min:0'],
                'notes'          => ['nullable', 'string', 'max:1000'],
            ],
            default => abort(404),
        };

        $validated = $request->validate($rules);

        if ($submodule === 'medical') {
            $validated['referred'] = $request->boolean('referred');
        }

        return $validated;
    }

    private function employeeOptions(Request $request): array
    {
        $query = Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }

    private function lineOptions(Request $request): array
    {
        $query = Line::query()->where('is_active', true)->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }

    private function payrollPeriodOptions(Request $request): array
    {
        $query = PayrollPeriod::query()->orderByDesc('year')->orderByDesc('month');
        $this->scopeToUserFactory($query, $request);

        return $query->get()->mapWithKeys(fn (PayrollPeriod $p) => [$p->id => $p->periodLabel()])->all();
    }
}
