<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsRentalVendor;
use App\Models\Tms\TmsVehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = TmsVehicle::query()->with(['factory', 'rentalVendor'])->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('admin.tms.vehicles.index', [
            'vehicles'  => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'statuses'  => config('tms.vehicle_statuses'),
            'filters'   => $request->only(['factory_id', 'status']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.tms.vehicles.form', [
            'vehicle'   => new TmsVehicle(['type' => 'own', 'fuel_type' => 'petrol', 'status' => 'available', 'passenger_capacity' => 4]),
            'factories' => $this->factoryOptions($request),
            'types'     => config('tms.vehicle_types'),
            'fuelTypes' => config('tms.fuel_types'),
            'statuses'  => config('tms.vehicle_statuses'),
            'paidBy'    => config('tms.fuel_paid_by'),
            'vendors'   => $this->vendorOptions($request),
        ]);
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

    public function edit(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);

        return view('admin.tms.vehicles.form', [
            'vehicle'   => $vehicle->load('rentalVendor'),
            'factories' => $this->factoryOptions($request),
            'types'     => config('tms.vehicle_types'),
            'fuelTypes' => config('tms.fuel_types'),
            'statuses'  => config('tms.vehicle_statuses'),
            'paidBy'    => config('tms.fuel_paid_by'),
            'vendors'   => $this->vendorOptions($request, $vehicle->factory_id),
        ]);
    }

    public function update(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);
        $vehicle->update($this->validateVehicle($request, $vehicle) + [
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.tms.vehicles.index')->with('success', 'Vehicle updated.');
    }

    public function destroy(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);
        $vehicle->delete();

        return redirect()->route('admin.tms.vehicles.index')->with('success', 'Vehicle deleted.');
    }

    private function validateVehicle(Request $request, ?TmsVehicle $vehicle = null): array
    {
        $data = $request->validate([
            'factory_id'             => ['required', 'exists:factories,id'],
            'name'                   => ['required', 'string', 'max:255'],
            'reg_number'             => [
                'required', 'string', 'max:32',
                Rule::unique('tms_vehicles', 'reg_number')
                    ->where('factory_id', $request->input('factory_id'))
                    ->ignore($vehicle?->id),
            ],
            'type'                   => ['required', 'in:own,rental'],
            'fuel_type'              => ['required', 'in:gas,petrol,diesel'],
            'passenger_capacity'     => ['required', 'integer', 'min:1', 'max:100'],
            'status'                 => ['required', 'in:available,on_trip,maintenance'],
            'rental_vendor_id'       => ['nullable', 'exists:tms_rental_vendors,id'],
            'rental_km_rate'         => ['nullable', 'numeric', 'min:0'],
            'fuel_covered_by'        => ['nullable', 'in:company,rental_party'],
            'maintenance_covered_by' => ['nullable', 'in:company,rental_party'],
        ]);

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
}
