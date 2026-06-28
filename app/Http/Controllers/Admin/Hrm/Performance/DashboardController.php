<?php

namespace App\Http\Controllers\Admin\Hrm\Performance;

use App\Http\Controllers\Admin\Hrm\Concerns\BuildsHrmModuleDashboard;
use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Services\Hrm\PerformanceDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use BuildsHrmModuleDashboard;
    use ScopesHrmFactory;

    public function index(Request $request, PerformanceDashboardService $dashboard)
    {
        abort_unless($request->user()?->canViewPerformanceSubmodule('dashboard'), 403);

        $filters = $this->dashboardFilters($request);

        return view('admin.hrm.performance.dashboard.index', array_merge(
            $dashboard->build($request->user(), $filters['factoryId'], $filters['from'], $filters['to']),
            $this->dashboardViewExtras($request, $filters),
        ));
    }
}
