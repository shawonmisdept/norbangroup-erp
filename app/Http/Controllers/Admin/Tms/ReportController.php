<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDailyOdometerLog;
use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsFuelLog;
use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsRentalVehicleCharge;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\DepartmentChargebackReportService;
use App\Services\Tms\DepartmentRequestReportService;
use App\Support\PortalDateTime;
use App\Services\Tms\FleetCostReportService;
use App\Services\Tms\FuelReportService;
use App\Services\Tms\PayrollOtExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private FleetCostReportService $fleetCostReport,
        private FuelReportService $fuelReport,
        private DepartmentRequestReportService $departmentRequestReport,
        private DepartmentChargebackReportService $departmentChargebackReport,
        private PayrollOtExportService $payrollOtExport,
    ) {}

    public function index(Request $request)
    {
        $tab = $request->input('tab', 'requests');
        $filters = $request->only(['factory_id', 'from', 'to', 'status', 'destination', 'driver_id', 'vehicle_id', 'workshop', 'payment_status', 'department_id', 'fuel_view']);
        $filters['fuel_view'] = ($filters['fuel_view'] ?? '') === 'by_vehicle' ? 'by_vehicle' : 'detail';

        $summary = match ($tab) {
            'fleet_cost'     => $this->fleetCostReport->summarize($request, $filters),
            'fuel'           => $this->fuelReport->summarize($request, $filters),
            'maintenance'    => $this->fleetCostReport->summarizeMaintenance($request, $filters),
            'rental_charges' => $this->fleetCostReport->summarizeRentalCharges($request, $filters),
            'ot'             => $this->fleetCostReport->summarizeDriverPay($request, $filters),
            default          => null,
        };

        $data = match ($tab) {
            'trips'                 => $this->tripRows($request, $filters),
            'fuel'                  => $filters['fuel_view'] === 'by_vehicle'
                ? $this->fuelReport->byVehicle($request, $filters)
                : $this->fuelRows($request, $filters),
            'odometer'              => $this->odometerRows($request, $filters),
            'ot'                    => $this->otRows($request, $filters),
            'maintenance'           => $this->maintenanceRows($request, $filters),
            'rental_charges'        => $this->rentalChargeRows($request, $filters),
            'fleet_cost'            => null,
            'requests_by_department'=> $this->departmentRequestReport->build($request, $filters),
            'department_chargeback' => $this->departmentChargebackReport->build($request, $filters),
            'payroll_ot'            => $this->payrollOtExport->rows($request, $filters),
            default                 => $this->requestRows($request, $filters),
        };

        $factoryId = isset($filters['factory_id']) ? (int) $filters['factory_id'] : null;

        return view('admin.tms.reports.index', [
            'tab'         => $tab,
            'factories'   => $this->factoryOptions($request),
            'departments' => $this->departmentOptions($request, $factoryId),
            'vehicles'    => $this->vehicleOptions($request, $factoryId),
            'workshops'   => $tab === 'maintenance' ? $this->workshopOptions($request, $factoryId) : [],
            'filters'     => $filters,
            'rows'        => $data,
            'summary'     => $summary,
            'statuses'    => config('tms.request_statuses'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'report'         => ['required', 'in:requests,trips,fuel,fuel_by_vehicle,odometer,ot,maintenance,rental_charges,fleet_cost,requests_by_department,department_chargeback,payroll_ot'],
            'factory_id'     => ['nullable', 'exists:factories,id'],
            'from'           => ['nullable', 'date'],
            'to'             => ['nullable', 'date', 'after_or_equal:from'],
            'payment_status' => ['nullable', 'in:pending,paid'],
            'department_id'  => ['nullable', 'integer'],
            'vehicle_id'     => ['nullable', 'integer', 'exists:tms_vehicles,id'],
            'workshop'       => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($validated['factory_id'])) {
            $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        }

        $filename = 'tms-' . $validated['report'] . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($validated, $request) {
            $out = fopen('php://output', 'w');

            match ($validated['report']) {
                'requests'       => $this->exportRequests($out, $request, $validated),
                'trips'          => $this->exportTrips($out, $request, $validated),
                'fuel'           => $this->exportFuel($out, $request, $validated),
                'fuel_by_vehicle'=> $this->exportFuelByVehicle($out, $request, $validated),
                'odometer'       => $this->exportOdometer($out, $request, $validated),
                'ot'             => $this->exportOt($out, $request, $validated),
                'maintenance'    => $this->exportMaintenance($out, $request, $validated),
                'rental_charges'        => $this->exportRentalCharges($out, $request, $validated),
                'fleet_cost'            => $this->exportFleetCost($out, $request, $validated),
                'requests_by_department'=> $this->exportRequestsByDepartment($out, $request, $validated),
                'department_chargeback' => $this->exportDepartmentChargeback($out, $request, $validated),
                'payroll_ot'            => $this->exportPayrollOt($out, $request, $validated),
            };

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function requestRows(Request $request, array $filters)
    {
        $query = TmsTransportRequest::with(['employee', 'destination', 'vehicle', 'driver.employee', 'rentalDriver', 'tripLog']);
        $this->scopeToUserFactory($query, $request);
        $this->applyCommonFilters($query, $filters, 'pickup_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['department_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                if ((int) $filters['department_id'] === 0) {
                    $q->whereNull('department_id');
                } else {
                    $q->where('department_id', $filters['department_id']);
                }
            });
        }

        return $query->latest('pickup_at')->paginate(25)->withQueryString();
    }

    private function tripRows(Request $request, array $filters)
    {
        $query = TmsTripLog::with(['transportRequests.employee', 'vehicle', 'driver.employee', 'rentalDriver']);
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

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

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
        $query = TmsDriverOvertimePayment::with(['tripLog.driver.employee', 'tripLog.rentalDriver', 'tripLog.vehicle']);
        $this->applyOtFilters($query, $request, $filters);

        return $query->latest('id')->paginate(25)->withQueryString();
    }

    private function maintenanceRows(Request $request, array $filters)
    {
        $query = TmsMaintenanceBill::with(['vehicle', 'items']);
        $this->scopeToUserFactory($query, $request);
        $this->applyCommonFilters($query, $filters, 'bill_date');

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (! empty($filters['workshop'])) {
            $query->where('workshop_name', $filters['workshop']);
        }

        return $query->latest('bill_date')->paginate(25)->withQueryString();
    }

    private function rentalChargeRows(Request $request, array $filters)
    {
        $query = TmsRentalVehicleCharge::with(['vehicle', 'rentalVendor', 'odometerLog']);
        $this->scopeToUserFactory($query, $request);
        $this->applyCommonFilters($query, $filters, 'log_date');

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
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

        $query = TmsTransportRequest::with(['employee', 'destination', 'vehicle', 'driver.employee', 'rentalDriver']);
        $this->scopeToUserFactory($query, $request);
        $this->applyDateFilters($query, 'pickup_at', $filters);

        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }

        foreach ($query->cursor() as $row) {
            fputcsv($out, [
                $row->id, $row->employee?->name, $row->pickup_location, $row->destinationLabel(),
                $row->pickup_at ? PortalDateTime::dateTime($row->pickup_at) : '', $row->passenger_count, $row->status,
                $row->trip_log_id, $row->vehicle?->displayLabel(), $row->assignedDriverLabel(),
            ]);
        }
    }

    private function exportTrips($out, Request $request, array $filters): void
    {
        fputcsv($out, ['ID', 'Passengers', 'Employees', 'Vehicle', 'Driver', 'KM', 'Duty Start', 'Duty End', 'Driver Pay', 'Rental Charge', 'Status']);

        $query = TmsTripLog::with(['transportRequests.employee', 'vehicle', 'driver.employee', 'rentalDriver']);
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
                $row->assignedDriverLabel(),
                $row->total_km,
                $row->duty_start_at ? PortalDateTime::dateTime($row->duty_start_at) : '',
                $row->duty_end_at ? PortalDateTime::dateTime($row->duty_end_at) : '',
                $row->total_driver_pay ?: $row->ot_amount,
                $row->rental_charge_amount,
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
        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        foreach ($query->cursor() as $row) {
            fputcsv($out, [
                $row->id, $row->vehicle?->displayLabel(), $row->trip_log_id, $row->fuel_type,
                $row->quantity, $row->unit_price, $row->amount, $row->paid_by, $row->created_at?->format('Y-m-d'),
            ]);
        }
    }

    private function exportFuelByVehicle($out, Request $request, array $filters): void
    {
        fputcsv($out, ['Vehicle', 'Entries', 'Total Qty', 'Total Amount', 'Company', 'Rental Party']);

        foreach ($this->fuelReport->byVehicle($request, $filters) as $row) {
            fputcsv($out, [
                $row->vehicle?->displayLabel() ?? '—',
                $row->entry_count,
                $row->total_quantity,
                $row->total_amount,
                $row->company_amount,
                $row->rental_party_amount,
            ]);
        }
    }

    private function exportOdometer($out, Request $request, array $filters): void
    {
        fputcsv($out, ['Date', 'Vehicle', 'Morning KM', 'Evening KM', 'Daily KM', 'Notes']);

        $query = TmsDailyOdometerLog::with('vehicle');
        $this->scopeToUserFactory($query, $request);
        $this->applyDateFilters($query, 'log_date', $filters);

        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }

        foreach ($query->cursor() as $row) {
            fputcsv($out, [
                $row->log_date?->format('Y-m-d'), $row->vehicle?->displayLabel(),
                $row->morning_km, $row->evening_km, $row->dailyKm(), $row->notes,
            ]);
        }
    }

    private function exportOt($out, Request $request, array $filters): void
    {
        fputcsv($out, ['Trip', 'Driver', 'Type', 'Night Bill', 'Holiday Bill', 'OT Hours', 'OT Hourly', 'Total', 'Status', 'Paid At']);

        $query = TmsDriverOvertimePayment::with(['tripLog.driver.employee', 'tripLog.rentalDriver']);
        $this->applyOtFilters($query, $request, $filters);

        foreach ($query->cursor() as $row) {
            $trip = $row->tripLog;
            $breakdown = $row->payment_breakdown ?? [];
            fputcsv($out, [
                $row->trip_log_id,
                $trip?->assignedDriverLabel() ?? $row->driver?->displayLabel(),
                $trip?->driver_type,
                $breakdown['night_bill_amount'] ?? $trip?->night_bill_amount,
                $breakdown['holiday_duty_amount'] ?? $trip?->holiday_duty_amount,
                $breakdown['ot_hours'] ?? $trip?->ot_hours,
                $breakdown['ot_hourly_amount'] ?? $trip?->ot_hourly_amount,
                $row->amount,
                $row->payment_status,
                $row->paid_at ? PortalDateTime::dateTime($row->paid_at) : '',
            ]);
        }
    }

    private function exportMaintenance($out, Request $request, array $filters): void
    {
        fputcsv($out, ['Bill No', 'Date', 'Vehicle', 'Workshop', 'Items', 'Total', 'Paid By']);

        $query = TmsMaintenanceBill::with('vehicle');
        $this->scopeToUserFactory($query, $request);
        $this->applyDateFilters($query, 'bill_date', $filters);

        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (! empty($filters['workshop'])) {
            $query->where('workshop_name', $filters['workshop']);
        }

        foreach ($query->cursor() as $row) {
            fputcsv($out, [
                $row->bill_no,
                $row->bill_date?->format('Y-m-d'),
                $row->vehicle?->displayLabel(),
                $row->workshop_name,
                $row->itemsDescription(),
                $row->total_amount,
                $row->paid_by,
            ]);
        }
    }

    private function exportRentalCharges($out, Request $request, array $filters): void
    {
        fputcsv($out, ['Date', 'Vehicle', 'Vendor', 'KM', 'Rate', 'Amount', 'Status', 'Paid At']);

        $query = TmsRentalVehicleCharge::with(['vehicle', 'rentalVendor']);
        $this->scopeToUserFactory($query, $request);
        $this->applyDateFilters($query, 'log_date', $filters);

        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }
        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        foreach ($query->cursor() as $row) {
            fputcsv($out, [
                $row->log_date?->format('Y-m-d') ?? $row->created_at?->format('Y-m-d'),
                $row->vehicle?->displayLabel(),
                $row->rentalVendor?->name,
                $row->total_km,
                $row->km_rate,
                $row->amount,
                $row->payment_status,
                $row->paid_at ? PortalDateTime::dateTime($row->paid_at) : '',
            ]);
        }
    }

    private function exportFleetCost($out, Request $request, array $filters): void
    {
        $summary = $this->fleetCostReport->summarize($request, $filters);

        fputcsv($out, ['Category', 'Total', 'Company', 'Rental Party', 'Paid', 'Pending']);

        fputcsv($out, ['Fuel', $summary['fuel_total'], $summary['fuel_company'], $summary['fuel_rental_party'], '', '']);
        fputcsv($out, ['Rental Vehicle Charges', $summary['rental_charges_total'], '', '', $summary['rental_charges_paid'], $summary['rental_charges_pending']]);
        fputcsv($out, ['Driver Pay', $summary['driver_pay_total'], '', '', $summary['driver_pay_paid'], $summary['driver_pay_pending']]);
        fputcsv($out, ['Maintenance', $summary['maintenance_total'], $summary['maintenance_company'], $summary['maintenance_rental_party'], '', '']);
        fputcsv($out, ['Grand Total', $summary['grand_total'], '', '', '', '']);
    }

    private function exportRequestsByDepartment($out, Request $request, array $filters): void
    {
        fputcsv($out, [
            'Department', 'Requests', 'Passengers', 'Pending', 'Approved', 'In Progress', 'Completed', 'Cancelled', 'Rejected',
        ]);

        foreach ($this->departmentRequestReport->build($request, $filters) as $row) {
            fputcsv($out, [
                $row->department_name,
                $row->request_count,
                $row->passenger_count,
                $row->pending_count,
                $row->approved_count,
                $row->in_progress_count,
                $row->completed_count,
                $row->cancelled_count,
                $row->rejected_count,
            ]);
        }
    }

    private function exportDepartmentChargeback($out, Request $request, array $filters): void
    {
        fputcsv($out, ['Department', 'Completed Trips', 'Passengers', 'Driver Pay Total', 'OT Hours']);

        foreach ($this->departmentChargebackReport->build($request, $filters) as $row) {
            fputcsv($out, [
                $row->department_name,
                $row->trip_count,
                $row->passenger_count,
                $row->driver_pay_total,
                $row->ot_hours_total,
            ]);
        }
    }

    private function exportPayrollOt($out, Request $request, array $filters): void
    {
        fputcsv($out, [
            'Period', 'Duty Date', 'Trip ID', 'Employee Code', 'Employee Name', 'Driver Type',
            'Vehicle', 'OT Hours', 'OT Hourly', 'Night Bill', 'Holiday Bill', 'Total Pay', 'Status', 'Paid At',
        ]);

        foreach ($this->payrollOtExport->rows($request, $filters) as $row) {
            fputcsv($out, [
                $row->period,
                $row->duty_date,
                $row->trip_id,
                $row->employee_code,
                $row->employee_name,
                $row->driver_type,
                $row->vehicle,
                $row->ot_hours,
                $row->ot_hourly_amount,
                $row->night_bill_amount,
                $row->holiday_duty_amount,
                $row->total_driver_pay,
                $row->payment_status,
                $row->paid_at,
            ]);
        }
    }

    /** @return \Illuminate\Support\Collection<int, TmsVehicle> */
    private function vehicleOptions(Request $request, ?int $factoryId = null): \Illuminate\Support\Collection
    {
        $query = TmsVehicle::orderBy('name');

        $fid = $factoryId ?? $request->user()?->scopedFactoryId();
        if ($fid) {
            $query->where('factory_id', $fid);
        }

        return $query->get();
    }

    /** @return list<string> */
    private function workshopOptions(Request $request, ?int $factoryId = null): array
    {
        $query = TmsMaintenanceBill::query()
            ->select('workshop_name')
            ->whereNotNull('workshop_name')
            ->where('workshop_name', '!=', '')
            ->distinct()
            ->orderBy('workshop_name');

        $fid = $factoryId ?? $request->user()?->scopedFactoryId();
        if ($fid) {
            $query->where('factory_id', $fid);
        }

        return $query->pluck('workshop_name')->values()->all();
    }

    /** @return array<int, string> */
    private function departmentOptions(Request $request, ?int $factoryId = null): array
    {
        $query = \App\Models\Department::query()
            ->where('is_active', true)
            ->orderBy('name');

        $fid = $factoryId ?? $request->user()?->factory_id;
        if ($fid) {
            $query->where('factory_id', $fid);
        }

        return [0 => 'Unassigned'] + $query->pluck('name', 'id')->all();
    }

    private function applyFactoryScope($query, Request $request, ?string $via = null): void
    {
        $factoryId = $request->user()?->scopedFactoryId();

        if ($factoryId) {
            if ($via) {
                $query->whereHas($via, fn ($q) => $q->where('factory_id', $factoryId));
            } else {
                $query->where('factory_id', $factoryId);
            }
        }
    }

    private function applyOtFilters($query, Request $request, array $filters): void
    {
        $this->applyFactoryScope($query, $request, 'tripLog');

        if (! empty($filters['factory_id'])) {
            $query->whereHas('tripLog', fn ($q) => $q->where('factory_id', $filters['factory_id']));
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
