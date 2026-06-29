<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsRentalVendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RentalVendorController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = TmsRentalVendor::query()->with('factory')->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return view('admin.tms.rental-vendors.index', [
            'vendors'   => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.tms.rental-vendors.form', [
            'vendor'    => new TmsRentalVendor(['status' => 'active']),
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateVendor($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        TmsRentalVendor::create($validated + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.tms.rental-vendors.index')->with('success', 'Rental vendor created.');
    }

    public function edit(Request $request, TmsRentalVendor $rentalVendor)
    {
        $this->authorizeFactoryAccess($request, $rentalVendor->factory_id);

        return view('admin.tms.rental-vendors.form', [
            'vendor'    => $rentalVendor,
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function update(Request $request, TmsRentalVendor $rentalVendor)
    {
        $this->authorizeFactoryAccess($request, $rentalVendor->factory_id);
        $rentalVendor->update($this->validateVendor($request, $rentalVendor) + [
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.tms.rental-vendors.index')->with('success', 'Rental vendor updated.');
    }

    public function destroy(Request $request, TmsRentalVendor $rentalVendor)
    {
        $this->authorizeFactoryAccess($request, $rentalVendor->factory_id);
        $rentalVendor->delete();

        return redirect()->route('admin.tms.rental-vendors.index')->with('success', 'Rental vendor deleted.');
    }

    private function validateVendor(Request $request, ?TmsRentalVendor $vendor = null): array
    {
        return $request->validate([
            'factory_id'     => ['required', 'exists:factories,id'],
            'name'           => [
                'required', 'string', 'max:255',
                Rule::unique('tms_rental_vendors', 'name')
                    ->where('factory_id', $request->input('factory_id'))
                    ->ignore($vendor?->id),
            ],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'mobile'         => ['nullable', 'string', 'max:32'],
            'rental_km_rate' => ['nullable', 'numeric', 'min:0'],
            'status'         => ['required', 'in:active,inactive'],
        ]);
    }
}
