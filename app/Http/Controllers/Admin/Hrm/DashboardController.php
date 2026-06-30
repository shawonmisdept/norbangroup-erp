<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\AttendanceDailyLog;
use App\Services\Hrm\HrmDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request, HrmDashboardService $dashboard)
    {
        $factoryId = $this->resolveFactoryId($request);
        $date = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();

        $data = $dashboard->buildOverview($request->user(), $factoryId, $date);

        return view('admin.hrm.dashboard.index', array_merge($data, [
            'factories' => $this->factoryOptions($request),
            'filters'   => [
                'factory_id' => $factoryId,
                'date'       => $date->toDateString(),
            ],
        ]));
    }

    public function todayAttendance(Request $request, HrmDashboardService $dashboard)
    {
        $factoryId = $this->resolveFactoryId($request);
        $date = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();
        $type = $request->input('type', 'all');

        $logs = $dashboard->todayAttendanceDetail(
            $request->user(),
            $factoryId,
            $date,
            $type,
            $request->only([
                'search', 'employee_code', 'name', 'department', 'designation', 'line', 'status',
            ])
        );

        return view('admin.hrm.dashboard.today-attendance', [
            'logs'       => $logs,
            'factories'  => $this->factoryOptions($request),
            'filters'    => array_merge(
                $request->only(['search', 'employee_code', 'name', 'department', 'designation', 'line', 'status']),
                [
                    'factory_id' => $factoryId,
                    'date'       => $date->toDateString(),
                    'type'       => $type,
                ]
            ),
            'type'       => $type,
            'dateLabel'  => $date->format('d M Y'),
            'typeLabels' => HrmDashboardService::TODAY_ATTENDANCE_TYPES,
            'statuses'   => AttendanceDailyLog::STATUSES,
        ]);
    }

    private function resolveFactoryId(Request $request): ?int
    {
        $requested = $request->filled('factory_id') ? (int) $request->factory_id : null;

        return $this->resolveFactoryFilter($request, $requested);
    }
}
