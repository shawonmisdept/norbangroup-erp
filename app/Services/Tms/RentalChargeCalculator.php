<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsTripLog;
use Illuminate\Validation\ValidationException;

class RentalChargeCalculator
{
    public function __construct(
        private RentalRateResolver $rateResolver,
    ) {}

    /** @return array{rental_km_rate: ?float, rental_charge_amount: float} */
    public function calculate(TmsTripLog $tripLog): array
    {
        $tripLog->loadMissing('vehicle.rentalVendor');

        $vehicle = $tripLog->vehicle;

        if (! $vehicle || $vehicle->type !== 'rental') {
            return [
                'rental_km_rate'       => null,
                'rental_charge_amount' => 0,
            ];
        }

        $totalKm = (float) ($tripLog->total_km ?? 0);

        if ($totalKm <= 0) {
            throw ValidationException::withMessages([
                'end_km' => 'Trip KM is required to calculate rental vehicle charge.',
            ]);
        }

        $rate = $this->rateResolver->resolve($vehicle);

        return [
            'rental_km_rate'       => $rate,
            'rental_charge_amount' => round($totalKm * $rate, 2),
        ];
    }
}
