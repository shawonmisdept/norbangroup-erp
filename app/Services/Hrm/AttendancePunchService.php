<?php

namespace App\Services\Hrm;

use App\Models\Factory;
use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\BiometricDevice;
use App\Models\Hrm\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttendancePunchService
{
    public function __construct(
        private AttendanceProcessor $processor,
        private GeofenceValidator $geofence,
        private HrmNotificationService $notifications,
    ) {}

    /** @param array<string, mixed> $record */
    public function recordFromDevice(BiometricDevice $device, array $record, string $source): ?AttendanceRawPunch
    {
        $normalized = $this->normalizeDeviceRecord($record, $device);

        if (! $normalized) {
            return null;
        }

        if ($this->isDeviceDuplicate($device, $normalized['external_id'])) {
            return null;
        }

        $employeeId = $this->resolveEmployeeId(
            $device->factory_id,
            $normalized['biometric_user_id']
        );

        $punch = AttendanceRawPunch::create([
            'factory_id'          => $device->factory_id,
            'biometric_device_id' => $device->id,
            'employee_id'         => $employeeId,
            'device_serial'       => $normalized['device_serial'],
            'biometric_user_id'   => $normalized['biometric_user_id'],
            'punched_at'          => $normalized['punched_at'],
            'punch_type'          => $normalized['punch_type'],
            'source'              => $source,
            'external_id'         => $normalized['external_id'],
            'raw_payload'         => $record,
        ]);

        $this->autoProcess($punch);

        if (! $employeeId) {
            $this->notifications->unmappedPunch($punch);
        }

        return $punch;
    }

    /** @param array<string, mixed> $meta */
    public function recordMobile(Employee $employee, string $punchType, array $meta): AttendanceRawPunch
    {
        $factory = $employee->factory;

        if (! $factory instanceof Factory || ! $factory->mobile_checkin_enabled) {
            throw ValidationException::withMessages([
                'check_in' => 'Mobile check-in is not enabled for your factory.',
            ]);
        }

        $latitude = (float) ($meta['latitude'] ?? 0);
        $longitude = (float) ($meta['longitude'] ?? 0);

        if ($latitude === 0.0 && $longitude === 0.0) {
            throw ValidationException::withMessages([
                'latitude' => 'Location is required. Please enable GPS on your phone.',
            ]);
        }

        $gatePoint = $meta['gate_point'] ?? null;
        $geo = $gatePoint
            ? $this->geofence->validateForGate($gatePoint, $latitude, $longitude)
            : $this->geofence->validateForFactory($factory, $latitude, $longitude);

        if (! $geo['valid']) {
            throw ValidationException::withMessages([
                'latitude' => $geo['message'] ?? 'You are outside the allowed check-in area.',
            ]);
        }

        $punchedAt = Carbon::now();
        $source = $gatePoint ? 'qr_scan' : 'mobile_gps';

        $this->guardDuplicateMobilePunch($employee, $punchedAt, $punchType);

        $photoPath = null;
        if (! empty($meta['photo'])) {
            $photoPath = $this->storePhoto($employee, $meta['photo']);
        }

        $punch = AttendanceRawPunch::create([
            'factory_id'        => $employee->factory_id,
            'employee_id'       => $employee->id,
            'biometric_user_id' => (string) ($employee->biometric_user_id ?: $employee->employee_code),
            'punched_at'        => $punchedAt,
            'punch_type'        => $punchType,
            'source'            => $source,
            'external_id'       => sha1("{$employee->id}|{$punchedAt->toDateTimeString()}|{$punchType}|{$source}"),
            'latitude'          => $latitude,
            'longitude'         => $longitude,
            'geo_distance_m'    => $geo['distance_m'],
            'photo_path'        => $photoPath,
            'gate_point_id'     => $gatePoint?->id,
            'raw_payload'       => [
                'channel'   => $source,
                'gate_code' => $gatePoint?->code,
            ],
        ]);

        $this->autoProcess($punch);

        return $punch;
    }

    public function recordManual(
        Employee $employee,
        Carbon $punchedAt,
        string $punchType,
        User $user,
        string $reason
    ): AttendanceRawPunch {
        $date = $punchedAt->copy()->startOfDay();
        $period = AttendancePeriod::getOrCreateForMonth($employee->factory_id, $date->year, $date->month);

        if ($period->isFrozen()) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Attendance period is frozen. Cannot add manual punch.',
            ]);
        }

        $externalId = sha1("manual|{$employee->id}|{$punchedAt->toDateTimeString()}|{$punchType}");

        if (AttendanceRawPunch::query()->where('external_id', $externalId)->exists()) {
            throw ValidationException::withMessages([
                'punched_at' => 'This punch already exists.',
            ]);
        }

        $punch = AttendanceRawPunch::create([
            'factory_id'         => $employee->factory_id,
            'employee_id'        => $employee->id,
            'biometric_user_id'  => (string) ($employee->biometric_user_id ?: $employee->employee_code),
            'punched_at'         => $punchedAt,
            'punch_type'         => $punchType,
            'source'             => 'manual_hr',
            'external_id'        => $externalId,
            'entered_by_user_id' => $user->id,
            'reason'             => $reason,
            'raw_payload'        => ['channel' => 'manual_hr'],
        ]);

        $this->autoProcess($punch);

        $this->notifications->manualPunchRecorded($punch);

        return $punch;
    }

    /** @param list<array<string, mixed>> $records */
    public function importDeviceRecords(BiometricDevice $device, array $records, string $source): array
    {
        $imported = 0;
        $skipped = 0;

        foreach ($records as $record) {
            $punch = $this->recordFromDevice($device, $record, $source);

            $punch ? $imported++ : $skipped++;
        }

        return [
            'fetched'  => count($records),
            'imported' => $imported,
            'skipped'  => $skipped,
        ];
    }

    /** @param array<string, mixed> $record */
    public function normalizeDeviceRecord(array $record, BiometricDevice $device): ?array
    {
        $userId = $record['user_id'] ?? $record['userid'] ?? $record['pin'] ?? $record['employee_code'] ?? null;
        $punchTime = $record['punch_time'] ?? $record['punch_time_iso'] ?? $record['timestamp'] ?? $record['time'] ?? null;

        if (! $userId || ! $punchTime) {
            return null;
        }

        try {
            $punchedAt = Carbon::parse($punchTime);
        } catch (\Throwable) {
            return null;
        }

        $externalId = (string) ($record['id'] ?? $record['external_id'] ?? $record['log_id'] ?? '');
        if ($externalId === '') {
            $externalId = sha1(implode('|', [
                $device->id,
                $userId,
                $punchedAt->toDateTimeString(),
                $record['punch_state'] ?? $record['state'] ?? '',
            ]));
        }

        return [
            'biometric_user_id' => (string) $userId,
            'punched_at'        => $punchedAt,
            'punch_type'        => $this->mapPunchType($record['punch_state'] ?? $record['state'] ?? $record['punch_type'] ?? null),
            'device_serial'     => (string) ($record['device_serial'] ?? $record['sn'] ?? $device->device_serial ?? ''),
            'external_id'       => $externalId,
        ];
    }

    public function isDeviceDuplicate(BiometricDevice $device, string $externalId): bool
    {
        return AttendanceRawPunch::query()
            ->where('biometric_device_id', $device->id)
            ->where('external_id', $externalId)
            ->exists();
    }

    public function resolveEmployeeId(int $factoryId, string $biometricUserId): ?int
    {
        return Employee::query()
            ->where('factory_id', $factoryId)
            ->where(function ($query) use ($biometricUserId) {
                $query->where('biometric_user_id', $biometricUserId)
                    ->orWhere('employee_code', $biometricUserId);
            })
            ->value('id');
    }

    public function mapPunchType(mixed $state): string
    {
        if ($state === null || $state === '') {
            return 'unknown';
        }

        $value = strtolower((string) $state);

        return match (true) {
            in_array($value, ['0', 'in', 'check_in', 'checkin'], true) => 'in',
            in_array($value, ['1', 'out', 'check_out', 'checkout'], true) => 'out',
            default => 'unknown',
        };
    }

    public function reprocessDay(Employee $employee, Carbon $punchedAt): void
    {
        $date = $punchedAt->copy()->startOfDay();
        $period = AttendancePeriod::getOrCreateForMonth($employee->factory_id, $date->year, $date->month);

        if ($period->isFrozen()) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Attendance period is frozen.',
            ]);
        }

        AttendanceRawPunch::query()
            ->where('employee_id', $employee->id)
            ->whereDate('punched_at', $date->toDateString())
            ->update(['processed_at' => null]);

        AttendanceDailyLog::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->delete();

        $this->processor->processDate($employee->factory_id, $date, $period);
        $this->processor->markAbsences($employee->factory_id, $date, $date, $period);

        AttendanceRawPunch::query()
            ->where('employee_id', $employee->id)
            ->whereDate('punched_at', $date->toDateString())
            ->whereNotNull('processed_at')
            ->update(['processed_at' => now()]);
    }

    private function autoProcess(AttendanceRawPunch $punch): void
    {
        if (! $punch->employee_id) {
            return;
        }

        $date = $punch->punched_at->copy()->startOfDay();
        $period = AttendancePeriod::getOrCreateForMonth(
            $punch->factory_id,
            $date->year,
            $date->month
        );

        if ($period->isFrozen()) {
            return;
        }

        $this->processor->processDate($punch->factory_id, $date, $period);

        AttendanceRawPunch::query()
            ->where('employee_id', $punch->employee_id)
            ->whereDate('punched_at', $date->toDateString())
            ->whereNull('processed_at')
            ->update(['processed_at' => now()]);
    }

    private function guardDuplicateMobilePunch(Employee $employee, Carbon $punchedAt, string $punchType): void
    {
        $exists = AttendanceRawPunch::query()
            ->where('employee_id', $employee->id)
            ->where('punch_type', $punchType)
            ->whereIn('source', ['mobile_gps', 'qr_scan'])
            ->whereBetween('punched_at', [
                $punchedAt->copy()->subMinutes(2),
                $punchedAt->copy()->addMinutes(2),
            ])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'check_in' => 'You already checked ' . ($punchType === 'in' ? 'in' : 'out') . ' recently. Please wait a moment.',
            ]);
        }
    }

    private function storePhoto(Employee $employee, string $base64Photo): string
    {
        if (! str_starts_with($base64Photo, 'data:image')) {
            throw ValidationException::withMessages([
                'photo' => 'Invalid photo format.',
            ]);
        }

        [, $data] = explode(',', $base64Photo, 2);
        $binary = base64_decode($data, true);

        if ($binary === false || strlen($binary) > 2_000_000) {
            throw ValidationException::withMessages([
                'photo' => 'Photo is invalid or too large (max 2MB).',
            ]);
        }

        $path = sprintf(
            'attendance-photos/%s/%s.jpg',
            $employee->employee_code,
            now()->format('Ymd_His')
        );

        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
