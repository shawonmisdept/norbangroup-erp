<?php

namespace App\Services\Hrm;

use App\Models\Hrm\BiometricDevice;
use App\Models\Hrm\BiometricSyncLog;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZKTecoAdmsSyncService
{
    public function __construct(
        private AdmsPunchImporter $importer,
        private HrmNotificationService $notifications,
    ) {}

    public function syncDevice(BiometricDevice $device): BiometricSyncLog
    {
        $log = BiometricSyncLog::create([
            'biometric_device_id' => $device->id,
            'status'              => 'running',
            'started_at'          => now(),
        ]);

        try {
            if (! $device->is_active) {
                throw new RuntimeException('Device is inactive.');
            }

            if (! $device->hasAdmsEndpoint()) {
                throw new RuntimeException('ADMS URL is not configured for this device.');
            }

            $records = $this->fetchRecords($device);
            $result = $this->importer->import($device, $records, 'adms_pull');

            $log->update([
                'status'           => 'success',
                'records_fetched'  => $result['fetched'],
                'records_imported' => $result['imported'],
                'records_skipped'  => $result['skipped'],
                'message'          => "Imported {$result['imported']} of {$result['fetched']} punch record(s).",
                'finished_at'      => now(),
            ]);

            $device->update([
                'last_synced_at'    => now(),
                'last_sync_status'  => 'success',
                'last_sync_message' => $log->message,
            ]);
        } catch (\Throwable $exception) {
            $log->update([
                'status'      => 'failed',
                'message'     => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            $device->update([
                'last_synced_at'    => now(),
                'last_sync_status'  => 'failed',
                'last_sync_message' => $exception->getMessage(),
            ]);

            $this->notifications->syncFailed($device->fresh(), $exception->getMessage());
        }

        return $log->fresh();
    }

    public function importPushPayload(BiometricDevice $device, array $payload): BiometricSyncLog
    {
        $log = BiometricSyncLog::create([
            'biometric_device_id' => $device->id,
            'status'              => 'running',
            'started_at'          => now(),
        ]);

        try {
            $records = $this->importer->extractRecordsFromPayload($payload);
            $result = $this->importer->import($device, $records, 'adms_push');

            $log->update([
                'status'           => 'success',
                'records_fetched'  => $result['fetched'],
                'records_imported' => $result['imported'],
                'records_skipped'  => $result['skipped'],
                'message'          => "Push import: {$result['imported']} of {$result['fetched']} record(s).",
                'finished_at'      => now(),
            ]);

            $device->update([
                'last_synced_at'    => now(),
                'last_sync_status'  => 'success',
                'last_sync_message' => $log->message,
            ]);
        } catch (\Throwable $exception) {
            $log->update([
                'status'      => 'failed',
                'message'     => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            $device->update([
                'last_synced_at'    => now(),
                'last_sync_status'  => 'failed',
                'last_sync_message' => $exception->getMessage(),
            ]);

            $this->notifications->syncFailed($device->fresh(), $exception->getMessage());
        }

        return $log->fresh();
    }

    private function fetchRecords(BiometricDevice $device): array
    {
        $since = $device->last_synced_at?->toDateTimeString();
        $path = config('hrm.adms.pull_path', '/api/attendance');
        $url = rtrim($device->adms_url, '/') . $path;

        $query = array_filter([
            'serial' => $device->device_serial,
            'since'  => $since,
        ]);

        $request = Http::timeout((int) config('hrm.adms.timeout', 30))
            ->acceptJson();

        $token = config('hrm.adms.api_token');
        if ($token) {
            $request = $request->withToken($token);
        }

        $response = $request->get($url, $query);

        if (! $response->successful()) {
            throw new RuntimeException("ADMS request failed (HTTP {$response->status()}).");
        }

        return $this->importer->extractRecordsFromPayload($response->json());
    }
}
