<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsGpsPosition;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TmsGpsService
{
    public function isEnabled(?int $factoryId = null): bool
    {
        return (bool) (TmsSetting::current()->gps_tracking_enabled ?? false);
    }

    public function providerForFactory(?int $factoryId = null): string
    {
        return TmsSetting::current()->gps_provider ?? 'none';
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
     * @param  array{latitude: float, longitude: float, accuracy_m?: ?float, speed_kmh?: ?float, heading?: ?float}|null  $coords
     */
    public function recordDriverCoords(
        TmsTripLog $trip,
        ?array $coords,
        string $event = 'browser',
    ): ?TmsGpsPosition {
        if ($coords === null || ! isset($coords['latitude'], $coords['longitude'])) {
            return null;
        }

        $trip->loadMissing('vehicle');

        if (! $trip->vehicle) {
            return null;
        }

        return $this->record(
            $trip->vehicle,
            (float) $coords['latitude'],
            (float) $coords['longitude'],
            $event,
            $trip,
            isset($coords['speed_kmh']) ? (float) $coords['speed_kmh'] : null,
            isset($coords['accuracy_m']) ? (float) $coords['accuracy_m'] : null,
            isset($coords['heading']) ? (float) $coords['heading'] : null,
        );
    }

    public function record(
        TmsVehicle $vehicle,
        float $latitude,
        float $longitude,
        string $source,
        ?TmsTripLog $trip = null,
        ?float $speedKmh = null,
        ?float $accuracyM = null,
        ?float $heading = null,
        ?Carbon $recordedAt = null,
    ): ?TmsGpsPosition {
        if (! $this->isEnabled($vehicle->factory_id)) {
            return null;
        }

        $provider = $this->providerForFactory($vehicle->factory_id);

        if ($provider === 'none') {
            return null;
        }

        $providerKey = str_starts_with($source, 'browser') ? 'browser' : $source;

        if ($providerKey === 'browser' && $provider !== 'browser') {
            return null;
        }

        if ($providerKey === 'device_api' && $provider !== 'device_api') {
            return null;
        }

        return TmsGpsPosition::create([
            'factory_id'   => $vehicle->factory_id,
            'vehicle_id'   => $vehicle->id,
            'trip_log_id'  => $trip?->id,
            'latitude'     => $latitude,
            'longitude'    => $longitude,
            'speed_kmh'    => $speedKmh,
            'heading'      => $heading,
            'accuracy_m'   => $accuracyM,
            'source'       => $source,
            'recorded_at'  => $recordedAt ?? now(),
        ]);
    }

    /**
     * @deprecated Use record() — kept for legacy stub callers/tests.
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
