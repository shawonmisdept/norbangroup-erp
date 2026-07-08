<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsMaintenanceBill;
use App\Models\Tms\TmsRentalVehicleCharge;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\VehiclePaperService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private VehiclePaperService $paperService,
    ) {}

    public function index(Request $request)
    {
        $requestQuery = TmsTransportRequest::query();
        $tripQuery = TmsTripLog::query();
        $otQuery = TmsDriverOvertimePayment::query()->where('payment_status', 'pending');
        $rentalQuery = TmsRentalVehicleCharge::query()->where('payment_status', 'pending');
        $maintenanceQuery = TmsMaintenanceBill::query()
            ->whereMonth('bill_date', now()->month)
            ->whereYear('bill_date', now()->year);
        $vehicleQuery = TmsVehicle::query();

        $factoryId = $request->user()?->scopedFactoryId();

        $this->scopeToUserFactory($requestQuery, $request);
        $this->scopeToUserFactory($tripQuery, $request);
        $this->scopeToUserFactory($maintenanceQuery, $request);
        $this->scopeToUserFactory($vehicleQuery, $request);

        if ($factoryId) {
            $otQuery->whereHas('tripLog', fn ($q) => $q->where('factory_id', $factoryId));
            $rentalQuery->where('factory_id', $factoryId);
        }

        $maintenanceStats = (clone $maintenanceQuery)
            ->selectRaw('COUNT(*) as bill_count, COALESCE(SUM(total_amount), 0) as spend_total')
            ->first();

        $vehicles = $vehicleQuery->get();
        $paperCounts = $this->paperService->dashboardCounts($vehicles);

        return view('admin.tms.dashboard', [
            'pendingRequests'             => (clone $requestQuery)->where('status', 'pending')->count(),
            'activeTrips'                 => (clone $tripQuery)->where('trip_status', 'in_progress')->count(),
            'otPending'                   => (clone $otQuery)->count(),
            'rentalChargesPending'        => (clone $rentalQuery)->count(),
            'maintenanceBillsThisMonth'   => (int) ($maintenanceStats->bill_count ?? 0),
            'maintenanceSpendThisMonth'   => (float) ($maintenanceStats->spend_total ?? 0),
            'papersExpired'               => $paperCounts['expired'],
            'papersUrgent'                => $paperCounts['urgent'],
            'papersWarning'               => $paperCounts['warning'],
            'recentRequests'              => (clone $requestQuery)->with(['employee', 'vehicle', 'driver.employee'])
                ->latest('id')->limit(10)->get(),
        ]);
    }
}
