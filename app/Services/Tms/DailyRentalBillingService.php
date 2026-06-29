<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsDailyOdometerLog;
use App\Models\Tms\TmsRentalVehicleCharge;

class DailyRentalBillingService
{
    public function __construct(
        private RentalRateResolver $rateResolver,
    ) {}

    public function syncFromOdometerLog(TmsDailyOdometerLog $log): ?TmsRentalVehicleCharge
    {
        $log->loadMissing('vehicle.rentalVendor');

        $vehicle = $log->vehicle;

        if (! $vehicle || $vehicle->type !== 'rental' || ! $log->hasEvening()) {
            return null;
        }

        $dailyKm = $log->dailyKm();

        if ($dailyKm === null || $dailyKm <= 0) {
            return null;
        }

        $rate = $this->rateResolver->resolve($vehicle);
        $amount = round($dailyKm * $rate, 2);

        $existing = TmsRentalVehicleCharge::where('odometer_log_id', $log->id)->first();

        return TmsRentalVehicleCharge::updateOrCreate(
            ['odometer_log_id' => $log->id],
            [
                'factory_id'       => $log->factory_id,
                'vehicle_id'       => $log->vehicle_id,
                'rental_vendor_id' => $vehicle->rental_vendor_id,
                'log_date'         => $log->log_date,
                'total_km'         => $dailyKm,
                'km_rate'          => $rate,
                'amount'           => $amount,
                'payment_status'   => $existing?->payment_status === 'paid' ? 'paid' : 'pending',
                'paid_at'          => $existing?->paid_at,
                'paid_by'          => $existing?->paid_by,
            ]
        );
    }
}
