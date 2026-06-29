<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsMaintenanceLog;
use App\Models\Tms\TmsVehicle;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    /** @param  array<int, array{part_name?: string, quantity?: mixed, unit_price?: mixed}>  $partsInput */
    public function save(TmsMaintenanceLog $log, array $partsInput, int $userId): TmsMaintenanceLog
    {
        return DB::transaction(function () use ($log, $partsInput, $userId) {
            $log->parts()->delete();

            $partsCost = 0.0;

            foreach ($partsInput as $part) {
                $name = trim((string) ($part['part_name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $qty = max(0, (float) ($part['quantity'] ?? 1));
                $unitPrice = max(0, (float) ($part['unit_price'] ?? 0));
                $amount = round($qty * $unitPrice, 2);
                $partsCost += $amount;

                $log->parts()->create([
                    'part_name'  => $name,
                    'quantity'   => $qty,
                    'unit_price' => $unitPrice,
                    'amount'     => $amount,
                ]);
            }

            $log->update([
                'parts_cost' => round($partsCost, 2),
                'total_cost' => round((float) $log->labor_cost + $partsCost, 2),
                'updated_by' => $userId,
            ]);

            $this->syncVehicleStatus($log->fresh(['vehicle']));

            return $log->fresh(['vehicle', 'parts']);
        });
    }

    public function syncVehicleStatus(TmsMaintenanceLog $log): void
    {
        $vehicle = $log->vehicle;

        if (! $vehicle) {
            return;
        }

        if ($vehicle->status === 'on_trip') {
            return;
        }

        if ($log->isOpen()) {
            $vehicle->update(['status' => 'maintenance']);

            return;
        }

        $hasOpen = TmsMaintenanceLog::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('status', 'open')
            ->where('id', '!=', $log->id)
            ->exists();

        if (! $hasOpen) {
            $vehicle->update(['status' => 'available']);
        }
    }

    public function defaultPaidBy(TmsVehicle $vehicle): string
    {
        return in_array($vehicle->maintenance_covered_by, ['company', 'rental_party'], true)
            ? $vehicle->maintenance_covered_by
            : 'company';
    }

    /** @return array<int, array{part_name: string, quantity: float, unit_price: float}> */
    public function normalizePartsInput(array $parts): array
    {
        return collect($parts)
            ->filter(fn ($part) => trim((string) ($part['part_name'] ?? '')) !== '')
            ->values()
            ->all();
    }
}
