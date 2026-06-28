<?php

namespace App\Http\Controllers\Admin\Hrm\Finance;

use App\Http\Controllers\Admin\Hrm\Concerns\BuildsHrmModuleDashboard;
use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Services\Hrm\FinanceDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use BuildsHrmModuleDashboard;
    use ScopesHrmFactory;

    public function index(Request $request, FinanceDashboardService $dashboard)
    {
        abort_unless($request->user()?->canViewFinanceSubmodule('dashboard'), 403);

        $filters = $this->dashboardFilters($request);

        return view('admin.hrm.finance.dashboard.index', array_merge(
            $dashboard->build($request->user(), $filters['factoryId'], $filters['from'], $filters['to']),
            $this->dashboardViewExtras($request, $filters),
        ));
    }
}
