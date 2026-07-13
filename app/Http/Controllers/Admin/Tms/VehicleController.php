<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsRentalVendor;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\VehiclePaperService;
use App\Support\TmsDriverVehiclePivot;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private VehiclePaperService $paperService,
    ) {}

    public function index(Request $request)
    {
        $query = TmsVehicle::query()
            ->with(['factory', 'rentalVendor', 'primaryDriver.employee', 'allocatedEmployee.designation'])
            ->orderBy('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('reg_number', 'like', "%{$term}%");
            });
        }

        $statsBase = clone $query;
        $allForStats = (clone $statsBase)->get();
        $paperCounts = $this->paperService->dashboardCounts($allForStats);

        if ($request->filled('paper_status')) {
            $status = $request->paper_status;
            $filtered = $query->get()->filter(
                fn (TmsVehicle $vehicle) => $this->paperService->worstStatusForVehicle($vehicle) === $status
            );
            $vehicles = $filtered->values();
            $paginator = null;
        } else {
            $vehicles = $query->paginate(25)->withQueryString();
            $paginator = $vehicles;
        }

        return view('admin.tms.vehicles.index', [
            'vehicles'     => $vehicles,
            'paginator'    => $paginator,
            'factories'    => $this->factoryOptions($request),
            'statuses'     => config('tms.vehicle_statuses'),
            'types'        => config('tms.vehicle_types'),
            'paperStatuses'=> [
                VehiclePaperService::STATUS_EXPIRED => 'Expired',
                VehiclePaperService::STATUS_URGENT  => 'Urgent',
                VehiclePaperService::STATUS_WARNING => 'Warning',
                VehiclePaperService::STATUS_OK      => 'All OK',
            ],
            'stats'        => [
                'total'    => $allForStats->count(),
                'available'=> $allForStats->where('status', 'available')->count(),
                'maintenance'=> $allForStats->where('status', 'maintenance')->count(),
                'papers'   => $paperCounts['expired'] + $paperCounts['urgent'],
            ],
            'paperService' => $this->paperService,
            'filters'      => $request->only(['factory_id', 'status', 'type', 'search', 'paper_status']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.tms.vehicles.form', $this->formData($request, new TmsVehicle([
            'type' => 'own', 'fuel_type' => 'petrol', 'status' => 'available',
            'passenger_capacity' => 4, 'registration_paper_status' => 'ok',
        ])));
    }

    public function store(Request $request)
    {
        $validated = $this->validateVehicle($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        TmsVehicle::create($validated + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.tms.vehicles.index')->with('success', 'Vehicle created.');
    }

    public function show(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);

        $relations = [
            'factory', 'rentalVendor', 'allocatedEmployee.designation',
            'primaryDriver.employee', 'defaultDrivers.employee',
            'paperRenewals.renewedByUser',
        ];

        if (TmsDriverVehiclePivot::available()) {
            $relations[] = 'assignedCompanyDrivers.employee';
        }

        $vehicle->load($relations);

        $recentTrips = $vehicle->tripLogs()
            ->with(['driver.employee', 'rentalDriver', 'transportRequests.employee'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.tms.vehicles.show', [
            'vehicle'      => $vehicle,
            'recentTrips'    => $recentTrips,
            'canManage'      => $request->user()?->canManageTmsSubmodule('vehicles') ?? false,
            'paperTypes'     => config('tms.paper_types'),
            'paperService'   => $this->paperService,
            'papers'         => $this->paperService->papersForVehicle($vehicle),
            'worstPaperStatus'=> $this->paperService->worstStatusForVehicle($vehicle),
        ]);
    }

    public function edit(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);

        return view('admin.tms.vehicles.form', $this->formData($request, $vehicle->load([
            'rentalVendor', 'allocatedEmployee', 'primaryDriver',
        ])));
    }

    public function update(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);
        $vehicle->update($this->validateVehicle($request, $vehicle) + [
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.tms.vehicles.show', $vehicle)->with('success', 'Vehicle updated.');
    }

    public function destroy(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);
        $vehicle->delete();

        return redirect()->route('admin.tms.vehicles.index')->with('success', 'Vehicle deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(Request $request, TmsVehicle $vehicle): array
    {
        $factoryId = $vehicle->factory_id ?? $request->user()?->factory_id;

        return [
            'vehicle'    => $vehicle,
            'factories'  => $this->factoryOptions($request),
            'types'      => config('tms.vehicle_types'),
            'fuelTypes'  => config('tms.fuel_types'),
            'statuses'   => config('tms.vehicle_statuses'),
            'paidBy'     => config('tms.fuel_paid_by'),
            'vendors'    => $this->vendorOptions($request, $factoryId),
            'employees'  => $this->employeeOptions($request),
            'drivers'    => $this->driverOptions($request),
            'categories' => config('tms.vehicle_categories'),
            'regStatuses'=> config('tms.registration_paper_statuses'),
        ];
    }

    private function validateVehicle(Request $request, ?TmsVehicle $vehicle = null): array
    {
        $fuelTypes = implode(',', array_keys(config('tms.fuel_types', [])));
        $categories = implode(',', array_keys(config('tms.vehicle_categories', [])));
        $regStatuses = implode(',', array_keys(config('tms.registration_paper_statuses', [])));

        $data = $request->validate([
            'factory_id'                 => ['required', 'exists:factories,id'],
            'name'                       => ['required', 'string', 'max:255'],
            'vehicle_category'           => ['nullable', 'in:' . $categories],
            'model_year'                 => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'engine_cc'                  => ['nullable', 'integer', 'min:50', 'max:10000'],
            'reg_number'                 => [
                'required', 'string', 'max:32',
                Rule::unique('tms_vehicles', 'reg_number')
                    ->where('factory_id', $request->input('factory_id'))
                    ->ignore($vehicle?->id),
            ],
            'type'                       => ['required', 'in:own,rental'],
            'fuel_type'                  => ['required', 'in:' . $fuelTypes],
            'passenger_capacity'         => ['required', 'integer', 'min:1', 'max:100'],
            'status'                     => ['required', 'in:available,on_trip,maintenance'],
            'purchase_date'              => ['nullable', 'date'],
            'registration_date'        => ['nullable', 'date'],
            'purchase_value'             => ['nullable', 'numeric', 'min:0'],
            'is_dedicated'               => ['sometimes', 'boolean'],
            'fitness_expires_at'         => ['nullable', 'date'],
            'tax_token_expires_at'       => ['nullable', 'date'],
            'insurance_expires_at'       => ['nullable', 'date'],
            'route_permit_expires_at'    => ['nullable', 'date'],
            'registration_paper_status'  => ['required', 'in:' . $regStatuses],
            'rental_vendor_id'           => ['nullable', 'exists:tms_rental_vendors,id'],
            'rental_km_rate'             => ['nullable', 'numeric', 'min:0'],
            'fuel_covered_by'            => ['nullable', 'in:company,rental_party'],
            'maintenance_covered_by'     => ['nullable', 'in:company,rental_party'],
            'allocated_employee_id'      => [
                'nullable',
                Rule::exists('hrm_employees', 'id')->where(function ($q) use ($request) {
                    $q->whereIn('status', ['active', 'probation']);

                    if ($scoped = $request->user()?->scopedFactoryId()) {
                        $q->where('factory_id', $scoped);
                    }
                }),
            ],
            // Drivers may operate vehicles from any unit.
            'primary_driver_id'          => [
                'nullable',
                Rule::exists('tms_drivers', 'id')->where(function ($q) use ($request) {
                    $q->where('status', 'active');

                    if ($scoped = $request->user()?->scopedFactoryId()) {
                        $q->where('factory_id', $scoped);
                    }
                }),
            ],
        ]);

        $data['is_dedicated'] = $request->boolean('is_dedicated');

        if ($data['type'] === 'rental') {
            $request->validate([
                'rental_vendor_id' => ['required', 'exists:tms_rental_vendors,id'],
            ]);
        } else {
            $data['rental_vendor_id'] = null;
            $data['rental_km_rate'] = null;
        }

        return $data;
    }

    private function vendorOptions(Request $request, ?int $factoryId = null): array
    {
        $query = TmsRentalVendor::query()->where('status', 'active')->orderBy('name');
        $fid = $factoryId ?? $request->user()?->factory_id;

        if ($fid) {
            $query->where('factory_id', $fid);
        } elseif ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return $query->pluck('name', 'id')->all();
    }

    private function employeeOptions(Request $request): array
    {
        $query = Employee::query()
            ->with(['designation', 'factory'])
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('name');

        // Cross-unit users see all units; unit-scoped users stay limited to their factory.
        $scoped = $request->user()?->scopedFactoryId();
        if ($scoped) {
            $query->where('factory_id', $scoped);
        }

        return $query->get()->mapWithKeys(function (Employee $employee) {
            $label = $employee->name;

            if ($employee->designation?->name) {
                $label .= ' (' . $employee->designation->name . ')';
            }

            if ($employee->factory?->name) {
                $label .= ' — ' . $employee->factory->name;
            }

            return [$employee->id => $label];
        })->all();
    }

    private function driverOptions(Request $request): array
    {
        $query = TmsDriver::query()
            ->with(['employee', 'factory'])
            ->where('status', 'active')
            ->orderBy('id');

        // Cross-unit users see every driver; unit-scoped users stay limited.
        $scoped = $request->user()?->scopedFactoryId();
        if ($scoped) {
            $query->where('factory_id', $scoped);
        }

        return $query->get()->mapWithKeys(function (TmsDriver $driver) {
            $label = $driver->displayLabel();

            if ($driver->factory?->name) {
                $label .= ' — ' . $driver->factory->name;
            }

            return [$driver->id => $label];
        })->all();
    }
}
