<?php

namespace App\Console\Commands;

use App\Jobs\Hrm\SyncBiometricDeviceJob;
use App\Models\Hrm\BiometricDevice;
use Illuminate\Console\Command;

class SyncAdmsDevicesCommand extends Command
{
    protected $signature = 'hrm:sync-adms {--device= : Sync a single biometric device ID} {--queue : Dispatch sync jobs to the queue}';

    protected $description = 'Pull attendance punches from configured ZKTeco ADMS devices';

    public function handle(): int
    {
        $query = BiometricDevice::query()
            ->where('is_active', true)
            ->whereNotNull('adms_url')
            ->where('adms_url', '!=', '');

        if ($deviceId = $this->option('device')) {
            $query->whereKey($deviceId);
        }

        $devices = $query->get();

        if ($devices->isEmpty()) {
            $this->warn('No active biometric devices with ADMS URL configured.');

            return self::SUCCESS;
        }

        foreach ($devices as $device) {
            if ($this->option('queue')) {
                SyncBiometricDeviceJob::dispatch($device->id);
                $this->line("Queued sync for device #{$device->id} ({$device->name}).");
            } else {
                SyncBiometricDeviceJob::dispatchSync($device->id);
                $this->line("Synced device #{$device->id} ({$device->name}).");
            }
        }

        return self::SUCCESS;
    }
}
