<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsMaintenanceBill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MaintenancePostingReportService
{
    /** @return array{groups: Collection, grand_total: float, workshop: string, from: ?string, to: ?string, factory_name: string, report_date: string} */
    public function build(Request $request, array $filters): array
    {
        $bills = $this->query($request, $filters)
            ->with(['vehicle.rentalVendor', 'vehicle.allocatedEmployee.designation', 'vehicle.allocatedEmployee.department', 'items'])
            ->orderBy('vehicle_id')
            ->orderBy('bill_date')
            ->orderBy('id')
            ->get();

        $serial = 0;
        $grandTotal = 0.0;

        $groups = $bills
            ->groupBy('vehicle_id')
            ->values()
            ->map(function (Collection $vehicleBills) use (&$serial, &$grandTotal) {
                $serial++;
                $vehicle = $vehicleBills->first()?->vehicle;
                $rows = $vehicleBills->map(function (TmsMaintenanceBill $bill) use (&$grandTotal) {
                    $grandTotal += (float) $bill->total_amount;

                    return [
                        'id'          => $bill->id,
                        'bill_no'     => $bill->bill_no,
                        'bill_date'   => $bill->bill_date?->format('d M Y'),
                        'description' => $bill->itemsDescription(),
                        'amount'      => (float) $bill->total_amount,
                        'posted'      => $bill->isPostedToFinance(),
                        'posted_at'   => $bill->posted_to_finance_at?->format('d M Y'),
                    ];
                })->values();

                return [
                    'sl'      => $serial,
                    'car_no'  => $vehicle?->postingCarNoLabel() ?? '—',
                    'user'    => $vehicle?->allocatedUserLabel() ?? '—',
                    'rows'    => $rows,
                    'rowspan' => max(1, $rows->count()),
                ];
            });

        $factoryName = $request->user()?->factory?->name ?? 'Company';

        if (! empty($filters['factory_id'])) {
            $factory = \App\Models\Factory::find($filters['factory_id']);
            $factoryName = $factory?->name ?? $factoryName;
        }

        return [
            'groups'       => $groups,
            'grand_total'  => round($grandTotal, 2),
            'workshop'     => (string) ($filters['workshop'] ?? ''),
            'from'         => $filters['from'] ?? null,
            'to'           => $filters['to'] ?? null,
            'factory_name' => $factoryName,
            'report_date'  => now()->format('d-M-Y'),
        ];
    }

    private function query(Request $request, array $filters): Builder
    {
        $query = TmsMaintenanceBill::query();

        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        } elseif ($request->user()?->factory_id) {
            $query->where('factory_id', $request->user()->factory_id);
        }

        if (! empty($filters['workshop'])) {
            $query->where('workshop_name', $filters['workshop']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('bill_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('bill_date', '<=', $filters['to']);
        }

        if (! empty($filters['unposted_only'])) {
            $query->whereNull('posted_to_finance_at');
        }

        return $query;
    }
}
