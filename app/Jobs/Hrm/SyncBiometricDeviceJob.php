<?php

namespace App\Jobs\Hrm;

use App\Models\Hrm\BiometricDevice;
use App\Services\Hrm\ZKTecoAdmsSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncBiometricDeviceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $deviceId,
    ) {
        $this->onQueue(config('hrm.queues.sync', 'hrm-sync'));
    }

    public function handle(ZKTecoAdmsSyncService $syncService): void
    {
        $device = BiometricDevice::query()->find($this->deviceId);

        if (! $device) {
            return;
        }

        $syncService->syncDevice($device);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Biometric sync job failed', [
            'device_id' => $this->deviceId,
            'message'   => $exception?->getMessage(),
        ]);
    }
}
