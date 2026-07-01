<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsGpsPosition;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use Illuminate\Support\Collection;

class TmsGpsService
{
    public function isEnabled(?int $factoryId): bool
    {
        if (! $factoryId) {
            return false;
        }

        $settings = TmsSetting::where('factory_id', $factoryId)->first();

        return (bool) ($settings?->gps_tracking_enabled ?? false);
    }

    /** @return Collection<int, TmsGpsPosition> */
    public function positionsForTrip(TmsTripLog $trip): Collection
    {
        return TmsGpsPosition::query()
            ->where('trip_log_id', $trip->id)
            ->orderBy('recorded_at')
            ->get();
    }

    /** @return Collection<int, TmsGpsPosition> */
    public function recentForVehicle(TmsVehicle $vehicle, int $limit = 50): Collection
    {
        return TmsGpsPosition::query()
            ->where('vehicle_id', $vehicle->id)
            ->latest('recorded_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Stub recorder for future device/browser integrations.
     * No-op unless GPS tracking is enabled for the factory.
     */
    public function recordStub(
        TmsVehicle $vehicle,
        float $latitude,
        float $longitude,
        ?TmsTripLog $trip = null,
        ?float $speedKmh = null,
    ): ?TmsGpsPosition {
        if (! $this->isEnabled($vehicle->factory_id)) {
            return null;
        }

        return TmsGpsPosition::create([
            'factory_id'   => $vehicle->factory_id,
            'vehicle_id'   => $vehicle->id,
            'trip_log_id'  => $trip?->id,
            'latitude'     => $latitude,
            'longitude'    => $longitude,
            'speed_kmh'    => $speedKmh,
            'source'       => 'stub',
            'recorded_at'  => now(),
        ]);
    }
}
