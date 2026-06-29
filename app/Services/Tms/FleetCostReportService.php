<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsFuelLog;
use App\Models\Tms\TmsMaintenanceLog;
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
        $maintenanceQuery = TmsMaintenanceLog::query();

        $this->applyFactory($fuelQuery, $request, $filters);
        $this->applyFactory($rentalQuery, $request, $filters);
        $this->applyFactory($maintenanceQuery, $request, $filters);
        $this->applyDriverFactory($driverQuery, $request, $filters);

        $this->applyDate($fuelQuery, $filters, 'created_at');
        $this->applyDate($rentalQuery, $filters, 'created_at');
        $this->applyDate($maintenanceQuery, $filters, 'service_date');

        if (! empty($filters['from'])) {
            $driverQuery->whereHas('tripLog', fn ($q) => $q->whereDate('duty_end_at', '>=', $filters['from']));
        }
        if (! empty($filters['to'])) {
            $driverQuery->whereHas('tripLog', fn ($q) => $q->whereDate('duty_end_at', '<=', $filters['to']));
        }

        $fuelTotal = (float) (clone $fuelQuery)->sum('amount');
        $fuelCompany = (float) (clone $fuelQuery)->where('paid_by', 'company')->sum('amount');
        $fuelRental = (float) (clone $fuelQuery)->where('paid_by', 'rental_party')->sum('amount');

        $rentalTotal = (float) (clone $rentalQuery)->sum('amount');
        $rentalPaid = (float) (clone $rentalQuery)->where('payment_status', 'paid')->sum('amount');
        $rentalPending = (float) (clone $rentalQuery)->where('payment_status', 'pending')->sum('amount');

        $driverTotal = (float) (clone $driverQuery)->sum('amount');
        $driverPaid = (float) (clone $driverQuery)->where('payment_status', 'paid')->sum('amount');
        $driverPending = (float) (clone $driverQuery)->where('payment_status', 'pending')->sum('amount');

        $maintenanceTotal = (float) (clone $maintenanceQuery)->sum('total_cost');
        $maintenanceCompany = (float) (clone $maintenanceQuery)->where('paid_by', 'company')->sum('total_cost');
        $maintenanceRental = (float) (clone $maintenanceQuery)->where('paid_by', 'rental_party')->sum('total_cost');

        $grandTotal = $fuelTotal + $rentalTotal + $driverTotal + $maintenanceTotal;

        return [
            'fuel_total'            => $fuelTotal,
            'fuel_company'          => $fuelCompany,
            'fuel_rental_party'     => $fuelRental,
            'rental_charges_total'  => $rentalTotal,
            'rental_charges_paid'   => $rentalPaid,
            'rental_charges_pending'=> $rentalPending,
            'driver_pay_total'      => $driverTotal,
            'driver_pay_paid'       => $driverPaid,
            'driver_pay_pending'    => $driverPending,
            'maintenance_total'     => $maintenanceTotal,
            'maintenance_company'   => $maintenanceCompany,
            'maintenance_rental_party' => $maintenanceRental,
            'grand_total'           => $grandTotal,
        ];
    }

    private function applyFactory(Builder $query, Request $request, array $filters): void
    {
        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        } elseif ($request->user()?->factory_id) {
            $query->where('factory_id', $request->user()->factory_id);
        }
    }

    private function applyDriverFactory(Builder $query, Request $request, array $filters): void
    {
        $factoryId = $filters['factory_id'] ?? $request->user()?->factory_id;

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
