<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsMaintenanceLog;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\MaintenanceService;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private MaintenanceService $maintenanceService,
    ) {}

    public function index(Request $request)
    {
        $query = TmsMaintenanceLog::query()
            ->with(['factory', 'vehicle', 'parts'])
            ->latest('service_date')
            ->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        return view('admin.tms.maintenance.index', [
            'logs'      => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'vehicles'  => $this->vehicleOptions($request),
            'statuses'  => config('tms.maintenance_statuses'),
            'filters'   => $request->only(['factory_id', 'status', 'vehicle_id']),
        ]);
    }

    public function create(Request $request)
    {
        $vehicle = null;
        if ($request->filled('vehicle_id')) {
            $vehicle = TmsVehicle::find($request->vehicle_id);
        }

        return view('admin.tms.maintenance.form', [
            'log'          => new TmsMaintenanceLog([
                'service_date' => now()->toDateString(),
                'service_type' => 'routine',
                'status'       => 'open',
                'paid_by'      => $vehicle ? $this->maintenanceService->defaultPaidBy($vehicle) : 'company',
                'vehicle_id'   => $vehicle?->id,
                'factory_id'   => $vehicle?->factory_id ?? $request->user()?->factory_id,
            ]),
            'factories'    => $this->factoryOptions($request),
            'vehicles'     => $this->vehicleOptions($request),
            'serviceTypes' => config('tms.maintenance_service_types'),
            'statuses'     => config('tms.maintenance_statuses'),
            'paidBy'       => config('tms.fuel_paid_by'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateLog($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $log = TmsMaintenanceLog::create($validated + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->maintenanceService->save(
            $log,
            $this->maintenanceService->normalizePartsInput($request->input('parts', [])),
            $request->user()->id,
        );

        return redirect()->route('admin.tms.maintenance.index')->with('success', 'Maintenance log created.');
    }

    public function edit(Request $request, TmsMaintenanceLog $maintenance)
    {
        $this->authorizeFactoryAccess($request, $maintenance->factory_id);

        return view('admin.tms.maintenance.form', [
            'log'          => $maintenance->load(['vehicle', 'parts']),
            'factories'    => $this->factoryOptions($request),
            'vehicles'     => $this->vehicleOptions($request, $maintenance->factory_id),
            'serviceTypes' => config('tms.maintenance_service_types'),
            'statuses'     => config('tms.maintenance_statuses'),
            'paidBy'       => config('tms.fuel_paid_by'),
        ]);
    }

    public function update(Request $request, TmsMaintenanceLog $maintenance)
    {
        $this->authorizeFactoryAccess($request, $maintenance->factory_id);

        $maintenance->update($this->validateLog($request) + [
            'updated_by' => $request->user()->id,
        ]);

        $this->maintenanceService->save(
            $maintenance,
            $this->maintenanceService->normalizePartsInput($request->input('parts', [])),
            $request->user()->id,
        );

        return redirect()->route('admin.tms.maintenance.index')->with('success', 'Maintenance log updated.');
    }

    public function destroy(Request $request, TmsMaintenanceLog $maintenance)
    {
        $this->authorizeFactoryAccess($request, $maintenance->factory_id);

        $vehicle = $maintenance->vehicle;
        $wasOpen = $maintenance->isOpen();
        $maintenance->delete();

        if ($wasOpen && $vehicle) {
            $stub = new TmsMaintenanceLog(['vehicle_id' => $vehicle->id, 'status' => 'closed']);
            $stub->setRelation('vehicle', $vehicle);
            $this->maintenanceService->syncVehicleStatus($stub);
        }

        return redirect()->route('admin.tms.maintenance.index')->with('success', 'Maintenance log deleted.');
    }

    private function validateLog(Request $request): array
    {
        $validated = $request->validate([
            'factory_id'   => ['required', 'exists:factories,id'],
            'vehicle_id'   => ['required', 'exists:tms_vehicles,id'],
            'service_date' => ['required', 'date'],
            'odometer_km'  => ['nullable', 'numeric', 'min:0'],
            'vendor_name'  => ['nullable', 'string', 'max:255'],
            'service_type' => ['required', 'in:routine,repair,accident'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'labor_cost'   => ['nullable', 'numeric', 'min:0'],
            'paid_by'      => ['required', 'in:company,rental_party'],
            'status'       => ['required', 'in:open,closed'],
            'notes'        => ['nullable', 'string', 'max:2000'],
            'parts'        => ['nullable', 'array'],
            'parts.*.part_name'  => ['nullable', 'string', 'max:255'],
            'parts.*.quantity'   => ['nullable', 'numeric', 'min:0'],
            'parts.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['labor_cost'] = $validated['labor_cost'] ?? 0;

        return $validated;
    }

    private function vehicleOptions(Request $request, ?int $factoryId = null): array
    {
        $query = TmsVehicle::orderBy('name');
        $fid = $factoryId ?? $request->user()?->factory_id;

        if ($fid) {
            $query->where('factory_id', $fid);
        } elseif ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return $query->get()->mapWithKeys(fn ($v) => [$v->id => $v->displayLabel()])->all();
    }
}
