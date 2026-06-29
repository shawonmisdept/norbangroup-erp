<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsMaintenanceLog;
use App\Models\Tms\TmsRentalVehicleCharge;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $requestQuery = TmsTransportRequest::query();
        $tripQuery = TmsTripLog::query();
        $otQuery = TmsDriverOvertimePayment::query()->where('payment_status', 'pending');
        $rentalQuery = TmsRentalVehicleCharge::query()->where('payment_status', 'pending');
        $maintenanceQuery = TmsMaintenanceLog::query()->where('status', 'open');
        $vehicleQuery = TmsVehicle::query()->where('status', 'maintenance');

        $this->scopeToUserFactory($requestQuery, $request);
        $this->scopeToUserFactory($tripQuery, $request);
        $this->scopeToUserFactory($otQuery, $request);
        $this->scopeToUserFactory($rentalQuery, $request);
        $this->scopeToUserFactory($maintenanceQuery, $request);
        $this->scopeToUserFactory($vehicleQuery, $request);

        return view('admin.tms.dashboard', [
            'pendingRequests'      => (clone $requestQuery)->where('status', 'pending')->count(),
            'activeTrips'          => (clone $tripQuery)->where('trip_status', 'in_progress')->count(),
            'otPending'            => (clone $otQuery)->count(),
            'rentalChargesPending' => (clone $rentalQuery)->count(),
            'openMaintenance'      => (clone $maintenanceQuery)->count(),
            'vehiclesInMaintenance'=> (clone $vehicleQuery)->count(),
            'recentRequests'       => (clone $requestQuery)->with(['employee', 'vehicle', 'driver.employee'])
                ->latest('id')->limit(10)->get(),
        ]);
    }
}
