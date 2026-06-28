<?php

namespace App\Http\Controllers\Admin\Hrm\Attendance;

use App\Http\Controllers\Admin\Hrm\Concerns\BuildsHrmModuleDashboard;
use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Services\Hrm\AttendanceDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use BuildsHrmModuleDashboard;
    use ScopesHrmFactory;

    public function index(Request $request, AttendanceDashboardService $dashboard)
    {
        abort_unless($request->user()?->canViewAttendanceSubmodule('dashboard'), 403);

        $filters = $this->dashboardFilters($request);

        return view('admin.hrm.attendance.dashboard.index', array_merge(
            $dashboard->build($request->user(), $filters['factoryId'], $filters['from'], $filters['to']),
            $this->dashboardViewExtras($request, $filters),
        ));
    }
}
