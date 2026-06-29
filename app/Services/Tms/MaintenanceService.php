<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsVehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    /** @param  array<int, array{item_name?: string, quantity?: mixed, unit?: mixed, amount?: mixed}>  $itemsInput */
    public function saveBill(TmsMaintenanceBill $bill, array $itemsInput, int $userId): TmsMaintenanceBill
    {
        return DB::transaction(function () use ($bill, $itemsInput, $userId) {
            $bill->items()->delete();

            $total = 0.0;
            $sort = 0;
            $now = now();
            $rows = [];

            foreach ($this->normalizeItemsInput($itemsInput) as $item) {
                $amount = round((float) $item['amount'], 2);
                $total += $amount;

                $rows[] = [
                    'maintenance_bill_id' => $bill->id,
                    'item_name'           => $item['item_name'],
                    'quantity'            => $item['quantity'],
                    'unit'                => $item['unit'],
                    'amount'              => $amount,
                    'sort_order'          => $sort++,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];
            }

            if ($rows !== []) {
                $bill->items()->insert($rows);
            }

            $bill->update([
                'total_amount' => round($total, 2),
                'updated_by'   => $userId,
            ]);

            return $bill->fresh(['vehicle.rentalVendor', 'items']);
        });
    }

    /**
     * @param  array{bill_no?: ?string, from?: ?string, to?: ?string, workshop?: ?string, item?: ?string}  $filters
     * @return array{bills: Collection<int, TmsMaintenanceBill>, workshops: list<string>, items: list<string>}
     */
    public function vehicleRegisterBundle(TmsVehicle $vehicle, array $filters = []): array
    {
        $allBills = TmsMaintenanceBill::query()
            ->where('vehicle_id', $vehicle->id)
            ->with('items')
            ->get();

        return [
            'bills'     => $this->filterBillCollection($allBills, $filters),
            'workshops' => $allBills->pluck('workshop_name')->filter()->unique()->sort()->values()->all(),
            'items'     => $allBills
                ->flatMap(fn (TmsMaintenanceBill $bill) => $bill->items->pluck('item_name'))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
        ];
    }

    /** @param  array{bill_no?: ?string, from?: ?string, to?: ?string, workshop?: ?string, item?: ?string}  $filters */
    public function billsForVehicleRegister(TmsVehicle $vehicle, array $filters = []): Collection
    {
        return $this->vehicleRegisterBundle($vehicle, $filters)['bills'];
    }

    /** @return Collection<int, Collection<int, TmsMaintenanceBill>> */
    public function billsGroupedByMonth(Collection $bills): Collection
    {
        return $bills
            ->sortBy([['bill_date', 'desc'], ['id', 'desc']])
            ->groupBy(fn (TmsMaintenanceBill $bill) => $bill->monthKey())
            ->sortKeysDesc();
    }

    /** @return list<string> */
    public function workshopOptions(?int $factoryId = null): array
    {
        $query = TmsMaintenanceBill::query()
            ->select('workshop_name')
            ->distinct()
            ->orderBy('workshop_name');

        if ($factoryId) {
            $query->where('factory_id', $factoryId);
        }

        return $query->pluck('workshop_name')->filter()->values()->all();
    }

    public function defaultPaidBy(TmsVehicle $vehicle): string
    {
        return in_array($vehicle->maintenance_covered_by, ['company', 'rental_party'], true)
            ? $vehicle->maintenance_covered_by
            : 'company';
    }

    /** @return array<int, array{item_name: string, quantity: ?float, unit: ?string, amount: float}> */
    public function normalizeItemsInput(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $name = trim((string) ($item['item_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $quantity = isset($item['quantity']) && $item['quantity'] !== '' && $item['quantity'] !== null
                ? (float) $item['quantity']
                : null;

            $unit = isset($item['unit']) && $item['unit'] !== '' ? (string) $item['unit'] : null;

            $normalized[] = [
                'item_name' => $name,
                'quantity'  => $quantity,
                'unit'      => $unit,
                'amount'    => max(0, (float) ($item['amount'] ?? 0)),
            ];
        }

        return $normalized;
    }

    /** @param  Collection<int, TmsMaintenanceBill>  $bills
     * @param  array{bill_no?: ?string, from?: ?string, to?: ?string, workshop?: ?string, item?: ?string}  $filters
     */
    private function filterBillCollection(Collection $bills, array $filters): Collection
    {
        return $bills->filter(function (TmsMaintenanceBill $bill) use ($filters) {
            if (! empty($filters['bill_no'])) {
                $needle = strtolower(trim((string) $filters['bill_no']));
                if (! str_contains(strtolower($bill->bill_no), $needle)) {
                    return false;
                }
            }

            if (! empty($filters['from']) && $bill->bill_date?->toDateString() < $filters['from']) {
                return false;
            }

            if (! empty($filters['to']) && $bill->bill_date?->toDateString() > $filters['to']) {
                return false;
            }

            if (! empty($filters['workshop'])) {
                $needle = strtolower(trim((string) $filters['workshop']));
                if (! str_contains(strtolower((string) $bill->workshop_name), $needle)) {
                    return false;
                }
            }

            if (! empty($filters['item'])) {
                $needle = strtolower(trim((string) $filters['item']));
                $matchesItem = $bill->items->contains(
                    fn ($item) => str_contains(strtolower((string) $item->item_name), $needle)
                );

                if (! $matchesItem) {
                    return false;
                }
            }

            return true;
        })->values();
    }
}
