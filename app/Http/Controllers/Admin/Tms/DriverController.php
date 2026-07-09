<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\DriverOtRateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DriverController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private DriverOtRateService $otRateService,
    ) {}

    public function index(Request $request)
    {
        $query = TmsDriver::query()->with(['factory', 'employee.designation', 'vehicles'])->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return view('admin.tms.drivers.index', [
            'drivers'   => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.tms.drivers.form', [
            'driver'             => new TmsDriver(['status' => 'active', 'is_overtime_active' => true, 'ot_rate' => 0]),
            'factories'          => $this->factoryOptions($request),
            'employees'          => $this->employeeOptions($request),
            'vehicles'           => $this->vehicleOptions($request),
            'assignedVehicleIds' => [],
            'primaryVehicleId'   => null,
        ]);
    }

    public function store(Request $request)
    {
        [$validated, $vehicleIds, $primaryVehicleId] = $this->validateDriver($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        $this->assertVehiclesBelongToFactory($vehicleIds, (int) $validated['factory_id']);

        $driver = TmsDriver::create($validated + [
            'default_vehicle_id' => $primaryVehicleId,
            'created_by'         => $request->user()->id,
            'updated_by'         => $request->user()->id,
        ]);

        $driver->syncAssignedVehicles($vehicleIds, $primaryVehicleId);
        $this->otRateService->record($driver, $request->user()->id);

        return redirect()->route('admin.tms.drivers.index')->with('success', 'Driver created.');
    }

    public function show(Request $request, TmsDriver $driver)
    {
        $this->authorizeFactoryAccess($request, $driver->factory_id);

        $driver->load(['factory', 'employee.designation', 'employee.department', 'vehicles', 'otRateLogs.recordedByUser']);

        $recentTrips = $driver->tripLogs()
            ->with(['vehicle', 'transportRequests.employee'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.tms.drivers.show', [
            'driver'      => $driver,
            'recentTrips' => $recentTrips,
            'canManage'   => $request->user()?->canManageTmsSubmodule('drivers') ?? false,
        ]);
    }

    public function edit(Request $request, TmsDriver $driver)
    {
        $this->authorizeFactoryAccess($request, $driver->factory_id);

        $driver->load(['employee', 'vehicles', 'otRateLogs.recordedByUser']);

        return view('admin.tms.drivers.form', [
            'driver'             => $driver,
            'factories'          => $this->factoryOptions($request),
            'employees'          => $this->employeeOptions($request, $driver->factory_id),
            'vehicles'           => $this->vehicleOptions($request, $driver->factory_id),
            'assignedVehicleIds' => $driver->assignedVehicleIds(),
            'primaryVehicleId'   => $driver->primaryVehicleId(),
        ]);
    }

    public function update(Request $request, TmsDriver $driver)
    {
        $this->authorizeFactoryAccess($request, $driver->factory_id);

        $before = $driver->fresh();

        [$validated, $vehicleIds, $primaryVehicleId] = $this->validateDriver($request, $driver);
        $this->assertVehiclesBelongToFactory($vehicleIds, (int) $validated['factory_id']);

        $driver->update($validated + [
            'default_vehicle_id' => $primaryVehicleId,
            'updated_by'         => $request->user()->id,
        ]);

        $driver->syncAssignedVehicles($vehicleIds, $primaryVehicleId);
        $this->otRateService->recordIfPayRulesChanged($driver->fresh(), $before, $request->user()->id);

        return redirect()->route('admin.tms.drivers.index')->with('success', 'Driver updated.');
    }

    public function destroy(Request $request, TmsDriver $driver)
    {
        $this->authorizeFactoryAccess($request, $driver->factory_id);
        $driver->delete();

        return redirect()->route('admin.tms.drivers.index')->with('success', 'Driver deleted.');
    }

    /** @return array{0: array<string, mixed>, 1: list<int>, 2: int} */
    private function validateDriver(Request $request, ?TmsDriver $driver = null): array
    {
        $validated = $request->validate([
            'factory_id'             => ['required', 'exists:factories,id'],
            'employee_id'            => [
                'required', 'exists:hrm_employees,id',
                Rule::unique('tms_drivers', 'employee_id')
                    ->where('factory_id', $request->input('factory_id'))
                    ->ignore($driver?->id),
            ],
            'vehicle_ids'            => ['required', 'array', 'min:1'],
            'vehicle_ids.*'          => ['required', 'integer', 'exists:tms_vehicles,id', 'distinct'],
            'primary_vehicle_id'     => ['required', 'integer', 'exists:tms_vehicles,id'],
            'license_number'         => ['nullable', 'string', 'max:64'],
            'ot_rate'                => ['required', 'numeric', 'min:0'],
            'is_overtime_active'     => ['nullable', 'boolean'],
            'ot_rate_effective_from' => ['nullable', 'date'],
            'status'                 => ['required', 'in:active,inactive'],
        ]) + ['is_overtime_active' => $request->boolean('is_overtime_active', true)];

        $vehicleIds = array_values(array_unique(array_map('intval', $validated['vehicle_ids'])));
        $primaryVehicleId = (int) $validated['primary_vehicle_id'];

        if (! in_array($primaryVehicleId, $vehicleIds, true)) {
            throw ValidationException::withMessages([
                'primary_vehicle_id' => 'Primary vehicle must be one of the assigned vehicles.',
            ]);
        }

        unset($validated['vehicle_ids'], $validated['primary_vehicle_id']);

        return [$validated, $vehicleIds, $primaryVehicleId];
    }

    /** @param  list<int>  $vehicleIds */
    private function assertVehiclesBelongToFactory(array $vehicleIds, int $factoryId): void
    {
        $count = TmsVehicle::query()
            ->where('factory_id', $factoryId)
            ->whereIn('id', $vehicleIds)
            ->count();

        if ($count !== count($vehicleIds)) {
            throw ValidationException::withMessages([
                'vehicle_ids' => 'All assigned vehicles must belong to the selected unit.',
            ]);
        }
    }

    private function employeeOptions(Request $request, ?int $factoryId = null): array
    {
        $query = Employee::query()
            ->with('designation')
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('name');

        $fid = $factoryId ?? $request->user()?->factory_id;
        if ($fid) {
            $query->where('factory_id', $fid);
        } elseif ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return $query->get()->mapWithKeys(function (Employee $employee) {
            $label = $employee->name;

            if ($employee->designation?->name) {
                $label .= ' (' . $employee->designation->name . ')';
            }

            return [$employee->id => $label];
        })->all();
    }

    private function vehicleOptions(Request $request, ?int $factoryId = null): array
    {
        $query = TmsVehicle::orderBy('name');
        $fid = $factoryId ?? $request->user()?->factory_id;
        if ($fid) {
            $query->where('factory_id', $fid);
        }

        return $query->get()->mapWithKeys(fn ($v) => [$v->id => $v->displayLabel() . ' (' . $v->passenger_capacity . ' seats)'])->all();
    }
}
