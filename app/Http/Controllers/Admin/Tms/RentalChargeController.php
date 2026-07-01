<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsRentalVehicleCharge;
use App\Models\Tms\TmsRentalVendor;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RentalChargeController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'factory_id'      => ['nullable', 'exists:factories,id'],
            'payment_status'  => ['nullable', 'in:pending,paid'],
            'rental_vendor_id'=> ['nullable', 'exists:tms_rental_vendors,id'],
            'from'            => ['nullable', 'date'],
            'to'              => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        if (! empty($filters['factory_id'])) {
            $this->authorizeFactoryAccess($request, (int) $filters['factory_id']);
        }

        $query = TmsRentalVehicleCharge::query()
            ->with(['vehicle', 'rentalVendor', 'tripLog', 'odometerLog', 'paidByUser'])
            ->latest('log_date')
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }
        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (! empty($filters['rental_vendor_id'])) {
            $query->where('rental_vendor_id', $filters['rental_vendor_id']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('log_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('log_date', '<=', $filters['to']);
        }

        $factoryId = $filters['factory_id'] ?? $request->user()?->factory_id;

        return view('admin.tms.rental-charges.index', [
            'charges'   => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'vendors'   => TmsRentalVendor::query()
                ->when($factoryId, fn ($q, $fid) => $q->where('factory_id', $fid))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'filters'   => $filters,
            'canManage' => $request->user()?->hasPermission('tms.rental_charges.manage') ?? false,
        ]);
    }

    public function markPaid(Request $request, TmsRentalVehicleCharge $charge)
    {
        $this->authorizeFactoryAccess($request, $charge->factory_id);

        if ($charge->payment_status === 'paid') {
            return back()->with('error', 'Charge is already marked as paid.');
        }

        $charge->update([
            'payment_status' => 'paid',
            'paid_at'        => now(),
            'paid_by'        => $request->user()->id,
        ]);

        return back()->with('success', 'Rental charge marked as paid.');
    }

    public function markUnpaid(Request $request, TmsRentalVehicleCharge $charge)
    {
        $this->authorizeFactoryAccess($request, $charge->factory_id);

        if ($charge->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'payment_status' => 'Only paid charges can be unmarked.',
            ]);
        }

        $charge->update([
            'payment_status' => 'pending',
            'paid_at'        => null,
            'paid_by'        => null,
        ]);

        return back()->with('success', 'Rental charge unmarked as paid.');
    }
}
