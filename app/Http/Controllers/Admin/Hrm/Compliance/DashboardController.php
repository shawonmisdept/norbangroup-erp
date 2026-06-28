<?php

namespace App\Http\Controllers\Admin\Hrm\Compliance;

use App\Http\Controllers\Admin\Hrm\Concerns\BuildsHrmModuleDashboard;
use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Services\Hrm\ComplianceDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use BuildsHrmModuleDashboard;
    use ScopesHrmFactory;

    public function index(Request $request, ComplianceDashboardService $dashboard)
    {
        abort_unless($request->user()?->canViewComplianceSubmodule('dashboard'), 403);

        $filters = $this->dashboardFilters($request);

        return view('admin.hrm.compliance.dashboard.index', array_merge(
            $dashboard->build($request->user(), $filters['factoryId'], $filters['from'], $filters['to']),
            $this->dashboardViewExtras($request, $filters),
        ));
    }
}
