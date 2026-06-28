<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDailyOdometerLog;
use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsFuelLog;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $tab = $request->input('tab', 'requests');
        $filters = $request->only(['factory_id', 'from', 'to', 'status', 'destination', 'driver_id', 'vehicle_id']);

        $data = match ($tab) {
            'trips'    => $this->tripRows($request, $filters),
            'fuel'     => $this->fuelRows($request, $filters),
            'odometer' => $this->odometerRows($request, $filters),
            'ot'       => $this->otRows($request, $filters),
            default    => $this->requestRows($request, $filters),
        };

        return view('admin.tms.reports.index', [
            'tab'       => $tab,
            'factories' => $this->factoryOptions($request),
            'filters'   => $filters,
            'rows'      => $data,
            'statuses'  => config('tms.request_statuses'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'report'     => ['required', 'in:requests,trips,fuel,odometer,ot'],
            'factory_id' => ['nullable', 'exists:factories,id'],
            'from'       => ['nullable', 'date'],
            'to'         => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        if (! empty($validated['factory_id'])) {
            $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        }

        $filename = 'tms-' . $validated['report'] . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($validated, $request) {
            $out = fopen('php://output', 'w');

            match ($validated['report']) {
                'requests' => $this->exportRequests($out, $request, $validated),
                'trips'    => $this->exportTrips($out, $request, $validated),
                'fuel'     => $this->exportFuel($out, $request, $validated),
                'odometer' => $this->exportOdometer($out, $request, $validated),
                'ot'       => $this->exportOt($out, $request, $validated),
            };

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function requestRows(Request $request, array $filters)
    {
        $query = TmsTransportRequest::with(['employee', 'destination', 'vehicle', 'driver.employee', 'tripLog']);
        $this->scopeToUserFactory($query, $request);
        $this->applyCommonFilters($query, $filters, 'pickup_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('pickup_at')->paginate(25)->withQueryString();
    }

    private function tripRows(Request $request, array $filters)
    {
        $query = TmsTripLog::with(['transportRequests.employee', 'vehicle', 'driver.employee']);
        $this->scopeToUserFactory($query, $request);
        $this->applyCommonFilters($query, $filters, 'created_at');

        if (! empty($filters['driver_id'])) {
            $query->where('driver_id', $filters['driver_id']);
        }
        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        return $query->latest('id')->paginate(25)->withQueryString();
    }

    private function fuelRows(Request $request, array $filters)
    {
        $query = TmsFuelLog::with(['vehicle', 'tripLog']);
        $this->scopeToUserFactory($query, $request);
        $this->applyCommonFilters($query, $filters, 'created_at');

        return $query->latest('id')->paginate(25)->withQueryString();
    }

    private function odometerRows(Request $request, array $filters)
    {
        $query = TmsDailyOdometerLog::with(['vehicle']);
        $this->scopeToUserFactory($query, $request);
        $this->applyCommonFilters($query, $filters, 'log_date');

        return $query->latest('log_date')->paginate(25)->withQueryString();
    }

    private function otRows(Request $request, array $filters)
    {
        $query = TmsDriverOvertimePayment::with(['tripLog.driver.employee', 'tripLog.vehicle']);
        $this->applyFactoryScope($query, $request, 'tripLog');

        if (! empty($filters['from'])) {
            $query->whereHas('tripLog', fn ($q) => $q->whereDate('duty_end_at', '>=', $filters['from']));
        }
        if (! empty($filters['to'])) {
            $query->whereHas('tripLog', fn ($q) => $q->whereDate('duty_end_at', '<=', $filters['to']));
        }

        return $query->latest('id')->paginate(25)->withQueryString();
    }

    private function applyCommonFilters($query, array $filters, string $dateColumn): void
    {
        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate($dateColumn, '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate($dateColumn, '<=', $filters['to']);
        }
    }

    private function exportRequests($out, Request $request, array $filters): void
    {
        fputcsv($out, ['ID', 'Employee', 'Pickup', 'Destination', 'Pickup At', 'Passengers', 'Status', 'Trip', 'Vehicle', 'Driver']);

        $query = TmsTransportRequest::with(['employee', 'destination', 'vehicle', 'driver.employee']);
        $this->scopeToUserFactory($query, $request);
        $this->applyDateFilters($query, 'pickup_at', $filters);

        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }

        foreach ($query->cursor() as $row) {
            fputcsv($out, [
                $row->id, $row->employee?->name, $row->pickup_location, $row->destinationLabel(),
                $row->pickup_at?->format('Y-m-d H:i'), $row->passenger_count, $row->status,
                $row->trip_log_id, $row->vehicle?->displayLabel(), $row->driver?->displayLabel(),
            ]);
        }
    }

    private function exportTrips($out, Request $request, array $filters): void
    {
        fputcsv($out, ['ID', 'Passengers', 'Employees', 'Vehicle', 'Driver', 'Duty Start', 'Duty End', 'OT Hours', 'OT Amount', 'Status']);

        $query = TmsTripLog::with(['transportRequests.employee', 'vehicle', 'driver.employee']);
        $this->scopeToUserFactory($query, $request);
        $this->applyDateFilters($query, 'created_at', $filters);

        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }

        foreach ($query->cursor() as $row) {
            fputcsv($out, [
                $row->id,
                $row->total_passengers,
                $row->transportRequests->pluck('employee.name')->filter()->implode(', '),
                $row->vehicle?->displayLabel(),
                $row->driver?->displayLabel(),
                $row->duty_start_at?->format('Y-m-d H:i'),
                $row->duty_end_at?->format('Y-m-d H:i'),
                $row->ot_hours,
                $row->ot_amount,
                $row->trip_status,
            ]);
        }
    }

    private function exportFuel($out, Request $request, array $filters): void
    {
        fputcsv($out, ['ID', 'Vehicle', 'Trip', 'Fuel Type', 'Quantity', 'Unit Price', 'Amount', 'Paid By', 'Date']);

        $query = TmsFuelLog::with(['vehicle', 'tripLog']);
        $this->scopeToUserFactory($query, $request);
        $this->applyDateFilters($query, 'created_at', $filters);

        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }

        foreach ($query->cursor() as $row) {
            fputcsv($out, [
                $row->id, $row->vehicle?->displayLabel(), $row->trip_log_id, $row->fuel_type,
                $row->quantity, $row->unit_price, $row->amount, $row->paid_by, $row->created_at?->format('Y-m-d'),
            ]);
        }
    }

    private function exportOdometer($out, Request $request, array $filters): void
    {
        fputcsv($out, ['Date', 'Vehicle', 'Morning KM', 'Evening KM', 'Daily KM', 'Notes']);

        $query = TmsDailyOdometerLog::with('vehicle');
        $this->scopeToUserFactory($query, $request);
        $this->applyDateFilters($query, 'log_date', $filters);

        foreach ($query->cursor() as $row) {
            fputcsv($out, [
                $row->log_date?->format('Y-m-d'), $row->vehicle?->displayLabel(),
                $row->morning_km, $row->evening_km, $row->dailyKm(), $row->notes,
            ]);
        }
    }

    private function exportOt($out, Request $request, array $filters): void
    {
        fputcsv($out, ['Trip', 'Driver', 'Amount', 'Status', 'Paid At']);

        $query = TmsDriverOvertimePayment::with(['tripLog.driver.employee']);
        $this->applyFactoryScope($query, $request, 'tripLog');

        foreach ($query->cursor() as $row) {
            fputcsv($out, [
                $row->trip_log_id, $row->driver?->displayLabel(), $row->amount,
                $row->payment_status, $row->paid_at?->format('Y-m-d H:i'),
            ]);
        }
    }

    private function applyFactoryScope($query, Request $request, ?string $via = null): void
    {
        if ($request->user()?->factory_id) {
            if ($via) {
                $query->whereHas($via, fn ($q) => $q->where('factory_id', $request->user()->factory_id));
            } else {
                $query->where('factory_id', $request->user()->factory_id);
            }
        }
    }

    private function applyDateFilters($query, string $column, array $filters): void
    {
        if (! empty($filters['from'])) {
            $query->whereDate($column, '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate($column, '<=', $filters['to']);
        }
    }
}
