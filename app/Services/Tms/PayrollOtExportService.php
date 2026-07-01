<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsDriverOvertimePayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PayrollOtExportService
{
    /** @return Collection<int, object> */
    public function rows(Request $request, array $filters): Collection
    {
        return $this->baseQuery($request, $filters)
            ->with([
                'tripLog.driver.employee',
                'tripLog.rentalDriver',
                'tripLog.vehicle',
            ])
            ->orderBy('id')
            ->get()
            ->map(function (TmsDriverOvertimePayment $payment) {
                $trip = $payment->tripLog;
                $employee = $trip?->driver?->employee;
                $breakdown = $payment->payment_breakdown ?? [];

                return (object) [
                    'period'              => $trip?->duty_end_at?->format('Y-m') ?? '',
                    'duty_date'           => $trip?->duty_end_at?->format('Y-m-d') ?? '',
                    'trip_id'             => $payment->trip_log_id,
                    'employee_code'       => $employee?->employee_code ?? '',
                    'employee_name'       => $employee?->name ?? ($trip?->rentalDriver?->name ?? ''),
                    'driver_type'         => $trip?->driver_type ?? '',
                    'vehicle'             => $trip?->vehicle?->displayLabel() ?? '',
                    'ot_hours'            => (float) ($breakdown['ot_hours'] ?? $trip?->ot_hours ?? 0),
                    'ot_hourly_amount'    => (float) ($breakdown['ot_hourly_amount'] ?? $trip?->ot_hourly_amount ?? 0),
                    'night_bill_amount'   => (float) ($breakdown['night_bill_amount'] ?? $trip?->night_bill_amount ?? 0),
                    'holiday_duty_amount' => (float) ($breakdown['holiday_duty_amount'] ?? $trip?->holiday_duty_amount ?? 0),
                    'total_driver_pay'    => (float) $payment->amount,
                    'payment_status'      => $payment->payment_status,
                    'paid_at'             => $payment->paid_at?->format('Y-m-d H:i'),
                ];
            });
    }

    /** @return Builder<TmsDriverOvertimePayment> */
    private function baseQuery(Request $request, array $filters): Builder
    {
        $query = TmsDriverOvertimePayment::query();

        $factoryId = $filters['factory_id'] ?? $request->user()?->factory_id;
        if ($factoryId) {
            $query->whereHas('tripLog', fn ($q) => $q->where('factory_id', $factoryId));
        }

        if (! empty($filters['from'])) {
            $query->whereHas('tripLog', fn ($q) => $q->whereDate('duty_end_at', '>=', $filters['from']));
        }

        if (! empty($filters['to'])) {
            $query->whereHas('tripLog', fn ($q) => $q->whereDate('duty_end_at', '<=', $filters['to']));
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query;
    }
}
