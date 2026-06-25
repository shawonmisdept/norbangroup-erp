<?php

namespace App\Services\Hrm;

use App\Models\Hrm\BiometricDevice;

class AdmsPunchImporter
{
    public function __construct(private AttendancePunchService $punchService) {}

    public function import(BiometricDevice $device, array $records, string $source): array
    {
        return $this->punchService->importDeviceRecords($device, $records, $source);
    }

    public function normalizeRecord(array $record, BiometricDevice $device): ?array
    {
        return $this->punchService->normalizeDeviceRecord($record, $device);
    }

    public function isDuplicate(BiometricDevice $device, string $externalId): bool
    {
        return $this->punchService->isDeviceDuplicate($device, $externalId);
    }

    public function resolveEmployeeId(int $factoryId, string $biometricUserId): ?int
    {
        return $this->punchService->resolveEmployeeId($factoryId, $biometricUserId);
    }

    public function mapPunchType(mixed $state): string
    {
        return $this->punchService->mapPunchType($state);
    }

    public function extractRecordsFromPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['records']) && is_array($payload['records'])) {
            return $payload['records'];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        return [$payload];
    }
}
