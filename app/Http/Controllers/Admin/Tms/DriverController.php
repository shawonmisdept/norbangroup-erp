<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Tms\TmsDriver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = TmsDriver::query()->with(['factory', 'employee', 'defaultVehicle'])->latest('id');
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
            'driver'    => new TmsDriver(['status' => 'active', 'is_overtime_active' => true, 'ot_rate' => 0]),
            'factories' => $this->factoryOptions($request),
            'employees' => $this->employeeOptions($request),
            'vehicles'  => $this->vehicleOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateDriver($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        TmsDriver::create($validated + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.tms.drivers.index')->with('success', 'Driver created.');
    }

    public function edit(Request $request, TmsDriver $driver)
    {
        $this->authorizeFactoryAccess($request, $driver->factory_id);

        return view('admin.tms.drivers.form', [
            'driver'    => $driver->load(['employee', 'defaultVehicle']),
            'factories' => $this->factoryOptions($request),
            'employees' => $this->employeeOptions($request, $driver->factory_id),
            'vehicles'  => $this->vehicleOptions($request, $driver->factory_id),
        ]);
    }

    public function update(Request $request, TmsDriver $driver)
    {
        $this->authorizeFactoryAccess($request, $driver->factory_id);
        $driver->update($this->validateDriver($request, $driver) + [
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.tms.drivers.index')->with('success', 'Driver updated.');
    }

    public function destroy(Request $request, TmsDriver $driver)
    {
        $this->authorizeFactoryAccess($request, $driver->factory_id);
        $driver->delete();

        return redirect()->route('admin.tms.drivers.index')->with('success', 'Driver deleted.');
    }

    private function validateDriver(Request $request, ?TmsDriver $driver = null): array
    {
        return $request->validate([
            'factory_id'             => ['required', 'exists:factories,id'],
            'employee_id'            => [
                'required', 'exists:hrm_employees,id',
                Rule::unique('tms_drivers', 'employee_id')
                    ->where('factory_id', $request->input('factory_id'))
                    ->ignore($driver?->id),
            ],
            'default_vehicle_id'     => ['required', 'exists:tms_vehicles,id'],
            'license_number'         => ['nullable', 'string', 'max:64'],
            'ot_rate'                => ['required', 'numeric', 'min:0'],
            'is_overtime_active'     => ['nullable', 'boolean'],
            'ot_rate_effective_from' => ['nullable', 'date'],
            'status'                 => ['required', 'in:active,inactive'],
        ]) + ['is_overtime_active' => $request->boolean('is_overtime_active', true)];
    }

    private function employeeOptions(Request $request, ?int $factoryId = null): array
    {
        $query = Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('name');

        $fid = $factoryId ?? $request->user()?->factory_id;
        if ($fid) {
            $query->where('factory_id', $fid);
        } elseif ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return $query->pluck('name', 'id')->all();
    }

    private function vehicleOptions(Request $request, ?int $factoryId = null): array
    {
        $query = \App\Models\Tms\TmsVehicle::orderBy('name');
        $fid = $factoryId ?? $request->user()?->factory_id;
        if ($fid) {
            $query->where('factory_id', $fid);
        }

        return $query->get()->mapWithKeys(fn ($v) => [$v->id => $v->displayLabel() . ' (' . $v->passenger_capacity . ' seats)'])->all();
    }
}
