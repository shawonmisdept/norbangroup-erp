<?php

namespace App\Http\Controllers\Rental\Concerns;

use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsRentalDriverPortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait ResolvesPortalRentalDriver
{
    protected function portalRentalDriver(Request $request): TmsRentalDriver
    {
        $portalUser = Auth::guard('rental_driver')->user();

        if (! $portalUser instanceof TmsRentalDriverPortalUser) {
            abort(403, 'Please sign in through the rental driver portal.');
        }

        $driver = $portalUser->relationLoaded('rentalDriver')
            ? $portalUser->rentalDriver
            : $portalUser->rentalDriver()->first();

        if (! $driver) {
            abort(403, 'Rental driver profile is not linked to this portal account.');
        }

        return $driver;
    }
}
