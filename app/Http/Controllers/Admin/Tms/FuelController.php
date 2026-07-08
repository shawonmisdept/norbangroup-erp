<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsFuelLog;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FuelController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = TmsFuelLog::query()->with(['vehicle', 'tripLog.transportRequest.employee', 'factory'])->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return view('admin.tms.fuel.index', [
            'fuelLogs'  => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
        ]);
    }

    public function show(Request $request, TmsFuelLog $fuelLog)
    {
        $this->authorizeFactoryAccess($request, $fuelLog->factory_id);

        $fuelLog->load(['vehicle', 'tripLog.transportRequest.employee', 'factory']);

        return view('admin.tms.fuel.show', [
            'fuelLog'   => $fuelLog,
            'canManage' => $request->user()?->canManageTmsSubmodule('fuel') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.tms.fuel.form', [
            'fuelLog'   => new TmsFuelLog(['paid_by' => 'company', 'unit' => 'litre']),
            'factories' => $this->factoryOptions($request),
            'fuelTypes' => config('tms.fuel_types'),
            'paidBy'    => config('tms.fuel_paid_by'),
            'trips'     => $this->completedTrips($request),
            'vehicles'  => $this->vehicles($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateFuel($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')->store('tms/fuel-receipts', 'public');
        }

        $validated['amount'] = round($validated['quantity'] * $validated['unit_price'], 2);
        $validated['created_by'] = $request->user()->id;

        TmsFuelLog::create($validated);

        return redirect()->route('admin.tms.fuel.index')->with('success', 'Fuel entry recorded.');
    }

    public function edit(Request $request, TmsFuelLog $fuelLog)
    {
        $this->authorizeFactoryAccess($request, $fuelLog->factory_id);

        return view('admin.tms.fuel.form', [
            'fuelLog'   => $fuelLog,
            'factories' => $this->factoryOptions($request),
            'fuelTypes' => config('tms.fuel_types'),
            'paidBy'    => config('tms.fuel_paid_by'),
            'trips'     => $this->completedTrips($request),
            'vehicles'  => $this->vehicles($request),
        ]);
    }

    public function update(Request $request, TmsFuelLog $fuelLog)
    {
        $this->authorizeFactoryAccess($request, $fuelLog->factory_id);

        $validated = $this->validateFuel($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        if ($request->hasFile('receipt')) {
            if ($fuelLog->receipt_path) {
                Storage::disk('public')->delete($fuelLog->receipt_path);
            }
            $validated['receipt_path'] = $request->file('receipt')->store('tms/fuel-receipts', 'public');
        }

        $validated['amount'] = round($validated['quantity'] * $validated['unit_price'], 2);

        $fuelLog->update($validated);

        return redirect()->route('admin.tms.fuel.index')->with('success', 'Fuel entry updated.');
    }

    public function destroy(Request $request, TmsFuelLog $fuelLog)
    {
        $this->authorizeFactoryAccess($request, $fuelLog->factory_id);

        if ($fuelLog->receipt_path) {
            Storage::disk('public')->delete($fuelLog->receipt_path);
        }

        $fuelLog->delete();

        return redirect()->route('admin.tms.fuel.index')->with('success', 'Fuel entry deleted.');
    }

    public function downloadReceipt(Request $request, TmsFuelLog $fuelLog)
    {
        $this->authorizeFactoryAccess($request, $fuelLog->factory_id);

        if (! $fuelLog->receipt_path || ! Storage::disk('public')->exists($fuelLog->receipt_path)) {
            abort(404, 'Receipt file not found.');
        }

        $extension = pathinfo($fuelLog->receipt_path, PATHINFO_EXTENSION) ?: 'bin';
        $filename = 'fuel-receipt-' . $fuelLog->id . ($fuelLog->receipt_number ? '-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $fuelLog->receipt_number) : '') . '.' . $extension;

        return Storage::disk('public')->download($fuelLog->receipt_path, $filename);
    }

    private function validateFuel(Request $request): array
    {
        $data = $request->validate([
            'factory_id'     => ['required', 'exists:factories,id'],
            'vehicle_id'     => ['required', 'exists:tms_vehicles,id'],
            'trip_log_id'    => ['nullable', 'exists:tms_trip_logs,id'],
            'fuel_type'      => ['required', Rule::in(array_keys(config('tms.fuel_types', [])))],
            'quantity'       => ['required', 'numeric', 'min:0.001'],
            'unit'           => ['required', 'string', 'max:16'],
            'unit_price'     => ['required', 'numeric', 'min:0'],
            'receipt_number' => ['nullable', 'string', 'max:64'],
            'receipt'        => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'paid_by'        => ['required', 'in:company,rental_party'],
        ]);

        return $data;
    }

    private function completedTrips(Request $request)
    {
        $query = TmsTripLog::with('transportRequest.employee')
            ->where('trip_status', 'completed')
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        return $query->limit(100)->get();
    }

    private function vehicles(Request $request)
    {
        $query = TmsVehicle::orderBy('name');
        $this->scopeToUserFactory($query, $request);

        return $query->get();
    }
}
