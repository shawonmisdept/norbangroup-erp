<?php

namespace App\Http\Controllers\Admin\Hrm\Compliance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Services\Hrm\WorkingHoursComplianceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WorkingHoursController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request, WorkingHoursComplianceService $compliance)
    {
        $factories = $this->factoryOptions($request);
        $factoryId = (int) ($request->factory_id ?? array_key_first($factories) ?? 0);
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        if ($factoryId && $request->user()?->factory_id) {
            $this->authorizeFactoryAccess($request, $factoryId);
        }

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $dailyViolations = $factoryId ? $compliance->dailyViolations($factoryId, $from, $to) : [];
        $weeklyViolations = $factoryId ? $compliance->weeklyViolations($factoryId, $from, $to) : [];

        return view('admin.hrm.compliance.working-hours.index', [
            'factories'        => $factories,
            'factoryId'        => $factoryId,
            'year'             => $year,
            'month'            => $month,
            'dailyViolations'  => $dailyViolations,
            'weeklyViolations' => $weeklyViolations,
            'filters'          => $request->only(['factory_id', 'year', 'month']),
            'canManage'        => $request->user()?->canManageComplianceSubmodule('working-hours') ?? false,
        ]);
    }

    public function notify(Request $request, WorkingHoursComplianceService $compliance)
    {
        $validated = $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'year'       => ['required', 'integer'],
            'month'      => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $from = Carbon::create($validated['year'], $validated['month'], 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $count = $compliance->notifyViolations((int) $validated['factory_id'], $from, $to);

        return redirect()->route('admin.hrm.compliance.working-hours.index', $validated)
            ->with('success', "Sent {$count} working hour limit alert(s) to HR.");
    }
}
