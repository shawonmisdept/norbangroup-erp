<?php

namespace App\Http\Controllers\Api\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\BiometricDevice;
use App\Services\Hrm\ZKTecoAdmsSyncService;
use Illuminate\Http\Request;

class AdmsPushController extends Controller
{
    public function store(Request $request, ZKTecoAdmsSyncService $syncService, ?BiometricDevice $device = null)
    {
        if (! $device) {
            $serial = $request->input('device_serial')
                ?? $request->input('sn')
                ?? $request->header('X-Device-Serial');

            $device = BiometricDevice::query()
                ->where('device_serial', $serial)
                ->where('is_active', true)
                ->first();
        }

        if (! $device) {
            return response()->json(['message' => 'Biometric device not found.'], 404);
        }

        $payload = $request->all();
        $log = $syncService->importPushPayload($device, $payload);

        return response()->json([
            'status'           => $log->status,
            'records_fetched'  => $log->records_fetched,
            'records_imported' => $log->records_imported,
            'records_skipped'  => $log->records_skipped,
            'message'          => $log->message,
        ]);
    }
}
