<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsFuelLog;
use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsRentalVehicleCharge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FleetCostReportService
{
    /** @return array<string, mixed> */
    public function summarize(Request $request, array $filters): array
    {
        $fuelQuery = TmsFuelLog::query();
        $rentalQuery = TmsRentalVehicleCharge::query();
        $driverQuery = TmsDriverOvertimePayment::query();
        $maintenanceQuery = TmsMaintenanceBill::query();

        $this->applyFactory($fuelQuery, $request, $filters);
        $this->applyFactory($rentalQuery, $request, $filters);
        $this->applyFactory($maintenanceQuery, $request, $filters);
        $this->applyDriverFactory($driverQuery, $request, $filters);

        $this->applyDate($fuelQuery, $filters, 'created_at');
        $this->applyDate($rentalQuery, $filters, 'created_at');
        $this->applyDate($maintenanceQuery, $filters, 'bill_date');

        if (! empty($filters['from'])) {
            $driverQuery->whereHas('tripLog', fn ($q) => $q->whereDate('duty_end_at', '>=', $filters['from']));
        }
        if (! empty($filters['to'])) {
            $driverQuery->whereHas('tripLog', fn ($q) => $q->whereDate('duty_end_at', '<=', $filters['to']));
        }

        $fuel = $this->sumAmountByColumns(clone $fuelQuery, 'amount', [
            'company'      => "paid_by = 'company'",
            'rental_party' => "paid_by = 'rental_party'",
        ]);

        $rental = $this->sumAmountByColumns(clone $rentalQuery, 'amount', [
            'paid'    => "payment_status = 'paid'",
            'pending' => "payment_status = 'pending'",
        ]);

        $driver = $this->sumAmountByColumns(clone $driverQuery, 'amount', [
            'paid'    => "payment_status = 'paid'",
            'pending' => "payment_status = 'pending'",
        ]);

        $maintenance = $this->sumAmountByColumns(clone $maintenanceQuery, 'total_amount', [
            'company'      => "paid_by = 'company'",
            'rental_party' => "paid_by = 'rental_party'",
        ]);

        $fuelTotal = (float) $fuel['total'];
        $rentalTotal = (float) $rental['total'];
        $driverTotal = (float) $driver['total'];
        $maintenanceTotal = (float) $maintenance['total'];

        return [
            'fuel_total'               => $fuelTotal,
            'fuel_company'             => (float) $fuel['company'],
            'fuel_rental_party'        => (float) $fuel['rental_party'],
            'rental_charges_total'     => $rentalTotal,
            'rental_charges_paid'      => (float) $rental['paid'],
            'rental_charges_pending'   => (float) $rental['pending'],
            'driver_pay_total'         => $driverTotal,
            'driver_pay_paid'          => (float) $driver['paid'],
            'driver_pay_pending'       => (float) $driver['pending'],
            'maintenance_total'        => $maintenanceTotal,
            'maintenance_company'      => (float) $maintenance['company'],
            'maintenance_rental_party' => (float) $maintenance['rental_party'],
            'grand_total'              => $fuelTotal + $rentalTotal + $driverTotal + $maintenanceTotal,
        ];
    }

    /** @return array{total: float, company: float, rental_party: float, bill_count: int} */
    public function summarizeMaintenance(Request $request, array $filters): array
    {
        $query = TmsMaintenanceBill::query();
        $this->applyFactory($query, $request, $filters);
        $this->applyDate($query, $filters, 'bill_date');

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (! empty($filters['workshop'])) {
            $query->where('workshop_name', $filters['workshop']);
        }

        $row = (clone $query)->selectRaw("
            COUNT(*) as bill_count,
            COALESCE(SUM(total_amount), 0) as total,
            COALESCE(SUM(CASE WHEN paid_by = 'company' THEN total_amount ELSE 0 END), 0) as company,
            COALESCE(SUM(CASE WHEN paid_by = 'rental_party' THEN total_amount ELSE 0 END), 0) as rental_party
        ")->first();

        return [
            'bill_count'   => (int) ($row->bill_count ?? 0),
            'total'        => (float) ($row->total ?? 0),
            'company'      => (float) ($row->company ?? 0),
            'rental_party' => (float) ($row->rental_party ?? 0),
        ];
    }

    /** @return array{total: float, paid: float, pending: float, entry_count: int} */
    public function summarizeRentalCharges(Request $request, array $filters): array
    {
        $query = TmsRentalVehicleCharge::query();
        $this->applyFactory($query, $request, $filters);
        $this->applyDate($query, $filters, 'log_date');

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        $row = (clone $query)->selectRaw("
            COUNT(*) as entry_count,
            COALESCE(SUM(amount), 0) as total,
            COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as paid,
            COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as pending
        ")->first();

        return [
            'entry_count' => (int) ($row->entry_count ?? 0),
            'total'       => (float) ($row->total ?? 0),
            'paid'        => (float) ($row->paid ?? 0),
            'pending'     => (float) ($row->pending ?? 0),
        ];
    }

    /** @return array{total: float, paid: float, pending: float, entry_count: int} */
    public function summarizeDriverPay(Request $request, array $filters): array
    {
        $query = TmsDriverOvertimePayment::query();
        $this->applyDriverFactory($query, $request, $filters);

        if (! empty($filters['from'])) {
            $query->whereHas('tripLog', fn ($q) => $q->whereDate('duty_end_at', '>=', $filters['from']));
        }
        if (! empty($filters['to'])) {
            $query->whereHas('tripLog', fn ($q) => $q->whereDate('duty_end_at', '<=', $filters['to']));
        }
        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $row = (clone $query)->selectRaw("
            COUNT(*) as entry_count,
            COALESCE(SUM(amount), 0) as total,
            COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as paid,
            COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as pending
        ")->first();

        return [
            'entry_count' => (int) ($row->entry_count ?? 0),
            'total'       => (float) ($row->total ?? 0),
            'paid'        => (float) ($row->paid ?? 0),
            'pending'     => (float) ($row->pending ?? 0),
        ];
    }

    /** @param  array<string, string>  $conditionalColumns */
    private function sumAmountByColumns(Builder $query, string $amountColumn, array $conditionalColumns): array
    {
        $selects = ["COALESCE(SUM({$amountColumn}), 0) as total"];

        foreach ($conditionalColumns as $alias => $condition) {
            $selects[] = "COALESCE(SUM(CASE WHEN {$condition} THEN {$amountColumn} ELSE 0 END), 0) as {$alias}";
        }

        $row = $query->selectRaw(implode(', ', $selects))->first();

        return array_merge(
            ['total' => (float) ($row->total ?? 0)],
            collect($conditionalColumns)->mapWithKeys(
                fn ($condition, $alias) => [$alias => (float) ($row->{$alias} ?? 0)]
            )->all()
        );
    }

    private function applyFactory(Builder $query, Request $request, array $filters): void
    {
        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        } elseif ($request->user()?->scopedFactoryId()) {
            $query->where('factory_id', $request->user()->scopedFactoryId());
        } elseif ($request->user()?->factory_id) {
            $query->where('factory_id', $request->user()->factory_id);
        }
    }

    private function applyDriverFactory(Builder $query, Request $request, array $filters): void
    {
        $factoryId = $filters['factory_id']
            ?? $request->user()?->scopedFactoryId()
            ?? $request->user()?->factory_id;

        if ($factoryId) {
            $query->whereHas('tripLog', fn ($q) => $q->where('factory_id', $factoryId));
        }
    }

    private function applyDate(Builder $query, array $filters, string $column): void
    {
        if (! empty($filters['from'])) {
            $query->whereDate($column, '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate($column, '<=', $filters['to']);
        }
    }
}
