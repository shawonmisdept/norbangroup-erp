<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\MaintenanceService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaintenanceController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private MaintenanceService $maintenanceService,
    ) {}

    public function index(Request $request)
    {
        $filters = $this->indexFilters($request);
        $filterOptions = $this->indexFilterOptions($request);

        $query = TmsVehicle::query()
            ->with(['factory', 'rentalVendor', 'allocatedEmployee.designation', 'allocatedEmployee.department'])
            ->withCount([
                'maintenanceBills as bills_count',
                'maintenanceBills as unposted_bills_count' => fn ($q) => $q->whereNull('posted_to_finance_at'),
            ])
            ->withMax('maintenanceBills as last_bill_date', 'bill_date')
            ->withSum('maintenanceBills as bills_total', 'total_amount')
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);
        $this->applyIndexFilters($query, $filters);

        if ($request->filled('factory_id')) {
            $factoryId = $this->resolveFactoryFilter($request, (int) $request->factory_id);
            if ($factoryId) {
                $query->where('factory_id', $factoryId);
            }
        }

        return view('admin.tms.maintenance.index', [
            'vehicles'             => $query->paginate(25)->withQueryString(),
            'factories'            => $this->factoryOptions($request),
            'filters'              => $filters,
            'vehicleOptions'       => $filterOptions['vehicles'],
            'postingCarOptions'    => $filterOptions['postingCarNos'],
            'allocatedUserOptions' => $filterOptions['allocatedUsers'],
            'types'                => config('tms.vehicle_types'),
        ]);
    }

    /** @return array{factory_id?: ?int, search?: ?string, vehicle_id?: ?int, posting_vehicle_id?: ?int, allocated_employee_id?: ?int, type?: ?string} */
    private function indexFilters(Request $request): array
    {
        return $request->validate([
            'factory_id'            => ['nullable', 'integer'],
            'search'                => ['nullable', 'string', 'max:255'],
            'vehicle_id'            => ['nullable', 'integer', 'exists:tms_vehicles,id'],
            'posting_vehicle_id'    => ['nullable', 'integer', 'exists:tms_vehicles,id'],
            'allocated_employee_id' => ['nullable', 'integer', 'exists:hrm_employees,id'],
            'type'                  => ['nullable', 'string', Rule::in(array_keys(config('tms.vehicle_types', [])))],
        ]);
    }

    /** @return array{vehicles: array<int, string>, postingCarNos: array<int, string>, allocatedUsers: array<int, string>} */
    private function indexFilterOptions(Request $request): array
    {
        $query = TmsVehicle::query()
            ->with(['rentalVendor', 'allocatedEmployee.designation'])
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $factoryId = $this->resolveFactoryFilter($request, (int) $request->factory_id);
            if ($factoryId) {
                $query->where('factory_id', $factoryId);
            }
        }

        $vehicles = $query->get();

        return [
            'vehicles' => $vehicles
                ->mapWithKeys(fn (TmsVehicle $vehicle) => [$vehicle->id => $vehicle->name])
                ->all(),
            'postingCarNos' => $vehicles
                ->mapWithKeys(fn (TmsVehicle $vehicle) => [$vehicle->id => $vehicle->reg_number])
                ->all(),
            'allocatedUsers' => $vehicles
                ->filter(fn (TmsVehicle $vehicle) => $vehicle->allocated_employee_id && $vehicle->allocatedUserLabel())
                ->unique('allocated_employee_id')
                ->sortBy(fn (TmsVehicle $vehicle) => $vehicle->allocatedUserLabel())
                ->mapWithKeys(fn (TmsVehicle $vehicle) => [$vehicle->allocated_employee_id => $vehicle->allocatedUserLabel()])
                ->all(),
        ];
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<TmsVehicle>  $query
     * @param  array{factory_id?: ?int, search?: ?string, vehicle_id?: ?int, posting_vehicle_id?: ?int, allocated_employee_id?: ?int, type?: ?string}  $filters
     */
    private function applyIndexFilters($query, array $filters): void
    {
        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('reg_number', 'like', "%{$search}%")
                    ->orWhereHas('allocatedEmployee', fn ($employee) => $employee
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('designation', fn ($designation) => $designation->where('name', 'like', "%{$search}%")))
                    ->orWhereHas('rentalVendor', fn ($vendor) => $vendor->where('name', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['vehicle_id'])) {
            $query->whereKey($filters['vehicle_id']);
        }

        if (! empty($filters['posting_vehicle_id'])) {
            $query->whereKey($filters['posting_vehicle_id']);
        }

        if (! empty($filters['allocated_employee_id'])) {
            $query->where('allocated_employee_id', $filters['allocated_employee_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
    }

    public function register(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);

        $filters = $this->registerFilters($request);

        return view('admin.tms.maintenance.register', $this->registerData($vehicle, $filters));
    }

    public function printRegister(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);

        $filters = $this->registerFilters($request);

        return view('admin.tms.maintenance.register-print', $this->registerData($vehicle, $filters));
    }

    /** @return array{bill_no?: ?string, from?: ?string, to?: ?string, workshop?: ?string, item?: ?string} */
    private function registerFilters(Request $request): array
    {
        return $request->validate([
            'bill_no'  => ['nullable', 'string', 'max:64'],
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date', 'after_or_equal:from'],
            'workshop' => ['nullable', 'string', 'max:255'],
            'item'     => ['nullable', 'string', 'max:255'],
        ]);
    }

    /** @param  array{bill_no?: ?string, from?: ?string, to?: ?string, workshop?: ?string, item?: ?string}  $filters
     * @return array{vehicle: TmsVehicle, monthGroups: \Illuminate\Support\Collection, filters: array, workshops: list<string>, items: list<string>, printUrl: string}
     */
    private function registerData(TmsVehicle $vehicle, array $filters = []): array
    {
        $vehicle->load(['factory', 'rentalVendor', 'allocatedEmployee.designation', 'allocatedEmployee.department']);

        $register = $this->maintenanceService->vehicleRegisterBundle($vehicle, $filters);

        $printUrl = route('admin.tms.maintenance.register.print', $vehicle);
        $query = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        if ($query !== []) {
            $printUrl .= '?' . http_build_query($query);
        }

        return [
            'vehicle'     => $vehicle,
            'monthGroups' => $this->maintenanceService->billsGroupedByMonth($register['bills']),
            'filters'     => $filters,
            'workshops'   => $register['workshops'],
            'items'       => $register['items'],
            'printUrl'    => $printUrl,
        ];
    }

    public function createBill(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);

        return view('admin.tms.maintenance.bill-form', [
            'vehicle' => $vehicle->load('rentalVendor'),
            'bill'    => new TmsMaintenanceBill([
                'vehicle_id' => $vehicle->id,
                'factory_id' => $vehicle->factory_id,
                'bill_date'  => now()->toDateString(),
                'paid_by'    => $this->maintenanceService->defaultPaidBy($vehicle),
            ]),
            'units'  => config('tms.maintenance_item_units', []),
            'paidBy' => config('tms.fuel_paid_by'),
        ]);
    }

    public function storeBill(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);

        $validated = $this->validateBill($request, $vehicle);

        try {
            $bill = TmsMaintenanceBill::create($validated + [
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        } catch (QueryException $e) {
            $this->throwIfDuplicateBillNo($e);
        }

        $this->maintenanceService->saveBill(
            $bill,
            $request->input('items', []),
            $request->user()->id,
        );

        return redirect()
            ->route('admin.tms.maintenance.register', $vehicle)
            ->with('success', 'Maintenance bill saved.');
    }

    public function editBill(Request $request, TmsMaintenanceBill $bill)
    {
        $this->authorizeFactoryAccess($request, $bill->factory_id);

        if ($bill->isPostedToFinance()) {
            return redirect()->route('admin.tms.maintenance.register', $bill->vehicle_id)
                ->with('error', 'Posted bills cannot be edited.');
        }

        return view('admin.tms.maintenance.bill-form', [
            'vehicle' => $bill->vehicle()->with('rentalVendor')->firstOrFail(),
            'bill'    => $bill->load('items'),
            'units'  => config('tms.maintenance_item_units', []),
            'paidBy' => config('tms.fuel_paid_by'),
        ]);
    }

    public function updateBill(Request $request, TmsMaintenanceBill $bill)
    {
        $this->authorizeFactoryAccess($request, $bill->factory_id);

        if ($bill->isPostedToFinance()) {
            return back()->with('error', 'Posted bills cannot be edited.');
        }

        $validated = $this->validateBill($request, $bill->vehicle, $bill);

        try {
            $bill->update($validated + [
                'updated_by' => $request->user()->id,
            ]);
        } catch (QueryException $e) {
            $this->throwIfDuplicateBillNo($e);
        }

        $this->maintenanceService->saveBill(
            $bill,
            $request->input('items', []),
            $request->user()->id,
        );

        return redirect()
            ->route('admin.tms.maintenance.register', $bill->vehicle_id)
            ->with('success', 'Maintenance bill updated.');
    }

    public function destroyBill(Request $request, TmsMaintenanceBill $bill)
    {
        $this->authorizeFactoryAccess($request, $bill->factory_id);

        if ($bill->isPostedToFinance()) {
            return back()->with('error', 'Posted bills cannot be deleted.');
        }

        $vehicleId = $bill->vehicle_id;
        $bill->delete();

        return redirect()
            ->route('admin.tms.maintenance.register', $vehicleId)
            ->with('success', 'Maintenance bill deleted.');
    }

    public function markPostedToFinance(Request $request, TmsMaintenanceBill $bill)
    {
        $this->authorizeFactoryAccess($request, $bill->factory_id);

        if ($bill->isPostedToFinance()) {
            return back()->with('error', 'This bill is already posted to finance.');
        }

        $bill->update([
            'posted_to_finance_at' => now(),
            'posted_to_finance_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.tms.maintenance.register', $bill->vehicle_id)
            ->with('success', 'Bill marked as posted to finance.');
    }

    public function unpostFromFinance(Request $request, TmsMaintenanceBill $bill)
    {
        $this->authorizeFactoryAccess($request, $bill->factory_id);

        $this->maintenanceService->unmarkPostedFromFinance($bill);

        return redirect()
            ->route('admin.tms.maintenance.register', $bill->vehicle_id)
            ->with('success', 'Bill unmarked from finance posting.');
    }

    private function validateBill(Request $request, TmsVehicle $vehicle, ?TmsMaintenanceBill $bill = null): array
    {
        $request->merge([
            'bill_no' => trim((string) $request->input('bill_no', '')),
        ]);

        $validated = $request->validate([
            'bill_no'       => [
                'required', 'string', 'max:64',
                Rule::unique('tms_maintenance_bills', 'bill_no')->ignore($bill?->id),
            ],
            'bill_date'     => ['required', 'date'],
            'workshop_name' => ['required', 'string', 'max:255'],
            'paid_by'       => ['required', 'in:company,rental_party'],
            'notes'         => ['nullable', 'string', 'max:2000'],
            'items'         => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.quantity'  => ['nullable', 'numeric', 'min:0'],
            'items.*.unit'      => ['nullable', 'string', 'max:16'],
            'items.*.amount'    => ['required', 'numeric', 'min:0'],
        ], [
            'bill_no.unique' => 'This bill / invoice number is already used.',
        ]);

        return $validated + [
            'factory_id' => $vehicle->factory_id,
            'vehicle_id' => $vehicle->id,
        ];
    }

    private function throwIfDuplicateBillNo(QueryException $e): never
    {
        $code = (int) ($e->errorInfo[1] ?? 0);

        if (in_array($code, [1062, 19], true) || str_contains(strtolower($e->getMessage()), 'unique')) {
            throw ValidationException::withMessages([
                'bill_no' => 'This bill / invoice number is already used.',
            ]);
        }

        throw $e;
    }
}
