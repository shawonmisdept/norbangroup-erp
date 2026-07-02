<?php

namespace App\Http\Controllers\Api\Tms;

use App\Http\Controllers\Controller;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\TmsGpsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GpsPositionController extends Controller
{
    public function store(Request $request, TmsGpsService $gpsService): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id'  => ['required', 'integer', 'exists:tms_vehicles,id'],
            'trip_log_id' => ['nullable', 'integer', 'exists:tms_trip_logs,id'],
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
            'speed_kmh'   => ['nullable', 'numeric', 'min:0'],
            'heading'     => ['nullable', 'numeric', 'min:0', 'max:360'],
            'accuracy_m'  => ['nullable', 'numeric', 'min:0'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $vehicle = TmsVehicle::findOrFail($validated['vehicle_id']);

        $trip = null;
        if (! empty($validated['trip_log_id'])) {
            $trip = TmsTripLog::query()
                ->whereKey($validated['trip_log_id'])
                ->where('vehicle_id', $vehicle->id)
                ->firstOrFail();
        }

        $position = $gpsService->record(
            $vehicle,
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            'device_api',
            $trip,
            isset($validated['speed_kmh']) ? (float) $validated['speed_kmh'] : null,
            isset($validated['accuracy_m']) ? (float) $validated['accuracy_m'] : null,
            isset($validated['heading']) ? (float) $validated['heading'] : null,
            isset($validated['recorded_at']) ? \Carbon\Carbon::parse($validated['recorded_at']) : null,
        );

        if (! $position) {
            return response()->json([
                'success' => false,
                'message' => 'GPS tracking is disabled for this unit or provider.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'id'      => $position->id,
        ]);
    }
}
