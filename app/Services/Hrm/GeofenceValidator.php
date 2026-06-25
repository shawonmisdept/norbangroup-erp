<?php

namespace App\Services\Hrm;

use App\Models\Factory;
use App\Models\Hrm\AttendanceGatePoint;

class GeofenceValidator
{
    /** @return array{valid: bool, distance_m: ?int, message: ?string} */
    public function validateForFactory(Factory $factory, float $latitude, float $longitude): array
    {
        if ($factory->attendance_lat === null || $factory->attendance_lng === null) {
            return ['valid' => true, 'distance_m' => null, 'message' => null];
        }

        $distance = $this->distanceMeters(
            (float) $factory->attendance_lat,
            (float) $factory->attendance_lng,
            $latitude,
            $longitude
        );

        $radius = (int) ($factory->attendance_radius_m ?: 200);
        $valid = $distance <= $radius;

        return [
            'valid'      => $valid,
            'distance_m' => $distance,
            'message'    => $valid
                ? null
                : "You are {$distance}m from the factory. Must be within {$radius}m.",
        ];
    }

    /** @return array{valid: bool, distance_m: ?int, message: ?string} */
    public function validateForGate(AttendanceGatePoint $gate, float $latitude, float $longitude): array
    {
        if ($gate->latitude === null || $gate->longitude === null) {
            return $this->validateForFactory($gate->factory, $latitude, $longitude);
        }

        $distance = $this->distanceMeters(
            (float) $gate->latitude,
            (float) $gate->longitude,
            $latitude,
            $longitude
        );

        $radius = (int) ($gate->factory->attendance_radius_m ?: 200);
        $valid = $distance <= $radius;

        return [
            'valid'      => $valid,
            'distance_m' => $distance,
            'message'    => $valid
                ? null
                : "You are {$distance}m from {$gate->name}. Must be within {$radius}m.",
        ];
    }

    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return (int) round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
