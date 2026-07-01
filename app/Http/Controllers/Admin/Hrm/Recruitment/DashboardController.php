<?php

namespace App\Http\Controllers\Admin\Hrm\Recruitment;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\RecruitmentOfferLetter;
use App\Services\Hrm\RecruitmentDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request, RecruitmentDashboardService $dashboard)
    {
        $this->ensureCanView($request);

        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->startOfDay();
        $factoryId = $request->filled('factory_id') ? (int) $request->factory_id : $request->user()?->factory_id;

        if ($factoryId && ! $request->user()?->factory_id) {
            $this->authorizeFactoryAccess($request, $factoryId);
        }

        return view('admin.hrm.recruitment.dashboard.index', array_merge(
            $dashboard->build($request->user(), $factoryId, $from, $to),
            [
                'factories'  => $this->factoryOptions($request),
                'statuses'   => config('hrm.recruitment_statuses', []),
                'filters'    => [
                    'factory_id' => $factoryId,
                    'from'       => $from->toDateString(),
                    'to'         => $to->toDateString(),
                ],
                'canManagePostings' => $request->user()?->hasPermission('hrm.recruitment.postings.manage') ?? false,
            ]
        ));
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.recruitment.applications.view')) {
            abort(403);
        }
    }
}
