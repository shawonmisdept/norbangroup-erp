<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDriverOvertimePayment;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $requestQuery = TmsTransportRequest::query();
        $tripQuery = TmsTripLog::query();
        $otQuery = TmsDriverOvertimePayment::query()->where('payment_status', 'pending');

        $this->scopeToUserFactory($requestQuery, $request);
        $this->scopeToUserFactory($tripQuery, $request);
        $this->scopeToUserFactory($otQuery, $request);

        return view('admin.tms.dashboard', [
            'pendingRequests' => (clone $requestQuery)->where('status', 'pending')->count(),
            'activeTrips'     => (clone $tripQuery)->where('trip_status', 'in_progress')->count(),
            'otPending'       => (clone $otQuery)->count(),
            'recentRequests'  => (clone $requestQuery)->with(['employee', 'vehicle', 'driver.employee'])
                ->latest('id')->limit(10)->get(),
        ]);
    }
}
