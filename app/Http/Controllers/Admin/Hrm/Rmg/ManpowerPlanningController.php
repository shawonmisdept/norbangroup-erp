<?php

namespace App\Http\Controllers\Admin\Hrm\Rmg;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Line;
use App\Models\Hrm\ManpowerPlan;
use App\Services\Hrm\HrmNotificationService;
use App\Services\Hrm\ManpowerPlanningService;
use Illuminate\Http\Request;

class ManpowerPlanningController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request, ManpowerPlanningService $planner, HrmNotificationService $notifier)
    {
        $factories = $this->factoryOptions($request);
        $factoryId = (int) ($request->factory_id ?? array_key_first($factories) ?? 0);
        $planDate = $request->input('plan_date', now()->toDateString());

        $query = ManpowerPlan::query()->with('line')->latest('plan_date');
        $this->scopeToUserFactory($query, $request);

        if ($factoryId) {
            $query->where('factory_id', $factoryId);
        }

        if ($request->filled('plan_date')) {
            $query->whereDate('plan_date', $planDate);
        }

        $summary = $factoryId
            ? $planner->lineSummary($factoryId, $planDate)
            : [];

        if ($factoryId && $summary !== []) {
            $notifier->manpowerVarianceIfNeeded($factoryId, $planDate, $summary);
        }

        return view('admin.hrm.rmg.manpower-planning.index', [
            'plans'     => $query->paginate(25)->withQueryString(),
            'summary'   => $summary,
            'factories' => $factories,
            'lines'     => $this->lineOptions($request),
            'filters'   => ['factory_id' => $factoryId, 'plan_date' => $planDate],
            'canManage' => $request->user()?->canManageRmgSubmodule('manpower-planning') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.rmg.manpower-planning.form', [
            'plan'      => new ManpowerPlan(['plan_date' => now()->toDateString(), 'required_count' => 10]),
            'factories' => $this->factoryOptions($request),
            'lines'     => $this->lineOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'factory_id'      => ['required', 'exists:factories,id'],
            'line_id'         => ['required', 'exists:hrm_lines,id'],
            'plan_date'       => ['required', 'date'],
            'required_count'  => ['required', 'integer', 'min:0', 'max:5000'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        ManpowerPlan::updateOrCreate(
            [
                'factory_id' => $validated['factory_id'],
                'line_id'    => $validated['line_id'],
                'plan_date'  => $validated['plan_date'],
            ],
            $validated + ['created_by' => $request->user()->id]
        );

        return redirect()->route('admin.hrm.rmg.manpower-planning.index', [
            'factory_id' => $validated['factory_id'],
            'plan_date'  => $validated['plan_date'],
        ])->with('success', 'Manpower plan saved.');
    }

    private function lineOptions(Request $request): array
    {
        $query = Line::query()->where('is_active', true)->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        return $query->pluck('name', 'id')->all();
    }
}
