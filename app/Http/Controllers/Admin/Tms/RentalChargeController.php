<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsRentalVehicleCharge;
use Illuminate\Http\Request;

class RentalChargeController extends Controller
{
    use ScopesHrmFactory;

    public function markPaid(Request $request, TmsRentalVehicleCharge $charge)
    {
        $this->authorizeFactoryAccess($request, $charge->factory_id);

        $charge->update([
            'payment_status' => 'paid',
            'paid_at'        => now(),
            'paid_by'        => $request->user()->id,
        ]);

        return back()->with('success', 'Rental charge marked as paid.');
    }
}
