<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsVehicle;

class RentalRateResolver
{
    public function resolve(TmsVehicle $vehicle): float
    {
        if ($vehicle->rental_km_rate !== null) {
            return (float) $vehicle->rental_km_rate;
        }

        $vehicle->loadMissing('rentalVendor');

        if ($vehicle->rentalVendor?->rental_km_rate !== null) {
            return (float) $vehicle->rentalVendor->rental_km_rate;
        }

        $settings = TmsSetting::where('factory_id', $vehicle->factory_id)->first();

        return (float) ($settings?->rental_km_rate ?? TmsSetting::defaultValues()['rental_km_rate']);
    }
}
