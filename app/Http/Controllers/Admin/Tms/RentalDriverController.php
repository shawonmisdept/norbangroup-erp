<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsRentalVendor;
use App\Models\Tms\TmsVehicle;
use Illuminate\Http\Request;
use App\Services\Tms\RentalDriverPortalService;
use App\Services\Tms\RentalDriverPhotoService;
use Illuminate\Validation\ValidationException;

class RentalDriverController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = TmsRentalDriver::query()->with(['factory', 'defaultVehicle', 'rentalVendor'])->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return view('admin.tms.rental-drivers.index', [
            'drivers'   => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
        ]);
    }

    public function create(Request $request)
    {
        $factoryId = (int) ($request->user()?->factory_id ?: array_key_first($this->factoryOptions($request)));

        return view('admin.tms.rental-drivers.form', [
            'driver'    => new TmsRentalDriver(['status' => 'active', 'factory_id' => $factoryId ?: null]),
            'factories' => $this->factoryOptions($request),
            'vehicles'  => $this->vehicleOptions($request, $factoryId ?: null),
            'vendors'   => $this->vendorOptions($request, $factoryId ?: null),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateDriver($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $driver = TmsRentalDriver::create($this->payloadFromValidated($validated) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        if ($request->hasFile('photo')) {
            $driver->update([
                'photo' => RentalDriverPhotoService::store($request->file('photo')),
            ]);
        }

        $this->syncPortalAccess($driver, $validated);

        return redirect()->route('admin.tms.rental-drivers.index')->with('success', 'Rental driver created.');
    }

    public function edit(Request $request, TmsRentalDriver $rentalDriver)
    {
        $this->authorizeFactoryAccess($request, $rentalDriver->factory_id);

        return view('admin.tms.rental-drivers.form', [
            'driver'    => $rentalDriver->load(['defaultVehicle', 'rentalVendor', 'portalUser']),
            'factories' => $this->factoryOptions($request),
            'vehicles'  => $this->vehicleOptions($request, $rentalDriver->factory_id),
            'vendors'   => $this->vendorOptions($request, $rentalDriver->factory_id),
        ]);
    }

    public function update(Request $request, TmsRentalDriver $rentalDriver)
    {
        $this->authorizeFactoryAccess($request, $rentalDriver->factory_id);
        $validated = $this->validateDriver($request);

        $rentalDriver->update($this->payloadFromValidated($validated) + [
            'updated_by' => $request->user()->id,
        ]);

        if ($request->hasFile('photo')) {
            $rentalDriver->update([
                'photo' => RentalDriverPhotoService::store($request->file('photo'), $rentalDriver->photo),
            ]);
        }

        $this->syncPortalAccess($rentalDriver->fresh(), $validated);

        return redirect()->route('admin.tms.rental-drivers.index')->with('success', 'Rental driver updated.');
    }

    public function destroy(Request $request, TmsRentalDriver $rentalDriver)
    {
        $this->authorizeFactoryAccess($request, $rentalDriver->factory_id);
        $rentalDriver->delete();

        return redirect()->route('admin.tms.rental-drivers.index')->with('success', 'Rental driver deleted.');
    }

    private function validateDriver(Request $request): array
    {
        $validated = $request->validate([
            'factory_id'         => ['required', 'exists:factories,id'],
            'name'               => ['required', 'string', 'max:255'],
            'mobile'             => ['nullable', 'string', 'max:32'],
            'nid_number'         => ['nullable', 'string', 'max:64'],
            'license_number'     => ['nullable', 'string', 'max:64'],
            'rental_vendor_id'   => ['nullable', 'exists:tms_rental_vendors,id'],
            'default_vehicle_id' => ['nullable', 'exists:tms_vehicles,id'],
            'status'             => ['required', 'in:active,inactive'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'photo'              => ['nullable', 'image', 'max:5120'],
            'portal_password'    => ['nullable', 'string', 'min:6', 'max:64'],
            'enable_portal'      => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['rental_vendor_id'])) {
            $vendor = TmsRentalVendor::query()->find($validated['rental_vendor_id']);

            if (! $vendor || (int) $vendor->factory_id !== (int) $validated['factory_id']) {
                throw ValidationException::withMessages([
                    'rental_vendor_id' => 'Selected vendor does not belong to this unit.',
                ]);
            }
        }

        return $validated;
    }

    /** @param  array<string, mixed>  $validated */
    private function payloadFromValidated(array $validated): array
    {
        $vendor = ! empty($validated['rental_vendor_id'])
            ? TmsRentalVendor::find($validated['rental_vendor_id'])
            : null;

        return [
            'factory_id'         => $validated['factory_id'],
            'name'               => $validated['name'],
            'mobile'             => $validated['mobile'] ?? null,
            'nid_number'         => $validated['nid_number'] ?? null,
            'license_number'     => $validated['license_number'] ?? null,
            'rental_vendor_id'   => $validated['rental_vendor_id'] ?? null,
            'vendor_name'        => $vendor?->name,
            'vendor_contact'     => $vendor?->mobile,
            'default_vehicle_id' => $validated['default_vehicle_id'] ?? null,
            'status'             => $validated['status'],
            'notes'              => $validated['notes'] ?? null,
        ];
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

    private function vendorOptions(Request $request, ?int $factoryId = null): array
    {
        $query = TmsRentalVendor::query()->where('status', 'active')->orderBy('name');
        $fid = $factoryId ?? $request->user()?->factory_id;

        if ($fid) {
            $query->where('factory_id', $fid);
        } elseif ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return $query->get()->mapWithKeys(fn ($v) => [$v->id => $v->dropdownLabel()])->all();
    }

    /** @param  array<string, mixed>  $validated */
    private function syncPortalAccess(TmsRentalDriver $driver, array $validated): void
    {
        if (empty($validated['enable_portal']) && empty($validated['portal_password'])) {
            return;
        }

        if (empty($driver->mobile)) {
            throw ValidationException::withMessages([
                'mobile' => 'Mobile number is required to enable rental driver portal access.',
            ]);
        }

        if (! empty($validated['portal_password'])) {
            RentalDriverPortalService::resetPassword($driver, $validated['portal_password']);
        } elseif (! empty($validated['enable_portal'])) {
            RentalDriverPortalService::createForDriver($driver);
        }
    }
}
