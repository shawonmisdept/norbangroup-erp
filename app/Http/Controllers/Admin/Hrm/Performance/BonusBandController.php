<?php

namespace App\Http\Controllers\Admin\Hrm\Performance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\PerformanceBonusBand;
use App\Services\Hrm\PerformanceBonusBandService;
use Illuminate\Http\Request;

class BonusBandController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private PerformanceBonusBandService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $factoryId = $request->integer('factory_id') ?: $request->user()?->factory_id;

        if (! $factoryId && count($this->factoryOptions($request)) === 1) {
            $factoryId = (int) array_key_first($this->factoryOptions($request));
        }

        $bands = $factoryId
            ? PerformanceBonusBand::query()
                ->where('factory_id', $factoryId)
                ->orderByDesc('min_score')
                ->get()
            : collect();

        if ($factoryId && $bands->isEmpty()) {
            $bands = $this->service->bandsForFactory($factoryId);
        }

        return view('admin.hrm.performance.bonus-bands.index', [
            'bands'             => $bands,
            'factories'         => $this->factoryOptions($request),
            'scopedFactoryName' => $this->scopedFactoryName($request),
            'factoryId'         => $factoryId,
            'canManage'         => $request->user()?->hasPermission('hrm.performance.bonus.manage') ?? false,
        ]);
    }

    public function edit(Request $request)
    {
        $this->ensureCanManage($request);

        $factoryId = $request->integer('factory_id') ?: $request->user()?->factory_id;

        if (! $factoryId) {
            abort(422, 'Select a factory to edit bonus bands.');
        }

        $this->authorizeFactoryAccess($request, $factoryId);

        $bands = PerformanceBonusBand::query()
            ->where('factory_id', $factoryId)
            ->orderByDesc('min_score')
            ->get();

        if ($bands->isEmpty()) {
            $bands = $this->service->bandsForFactory($factoryId);
        }

        return view('admin.hrm.performance.bonus-bands.form', [
            'factoryId' => $factoryId,
            'factoryName' => $this->factoryOptions($request)[$factoryId] ?? 'Factory',
            'canManage' => true,
            'bands' => $bands->map(fn (PerformanceBonusBand $b) => [
                'name'          => $b->name,
                'min_score'     => $b->min_score,
                'max_score'     => $b->max_score,
                'bonus_percent' => $b->bonus_percent,
                'sort_order'    => $b->sort_order,
                'is_active'     => $b->is_active,
            ])->values()->all(),
        ]);
    }

    public function update(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'factory_id'                 => ['required', 'exists:factories,id'],
            'bands'                      => ['required', 'array', 'min:1'],
            'bands.*.name'               => ['required', 'string', 'max:100'],
            'bands.*.min_score'          => ['required', 'numeric', 'min:0', 'max:100'],
            'bands.*.max_score'          => ['required', 'numeric', 'min:0', 'max:100'],
            'bands.*.bonus_percent'      => ['required', 'numeric', 'min:0', 'max:200'],
            'bands.*.sort_order'         => ['nullable', 'integer', 'min:0'],
            'bands.*.is_active'          => ['nullable', 'boolean'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $this->service->syncFactoryBands((int) $validated['factory_id'], $validated['bands']);

        return redirect()->route('admin.hrm.performance.bonus-bands.index', ['factory_id' => $validated['factory_id']])
            ->with('success', 'Bonus bands updated.');
    }

    public function reset(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        PerformanceBonusBand::query()->where('factory_id', $validated['factory_id'])->delete();
        $this->service->seedDefaultBands((int) $validated['factory_id']);

        return back()->with('success', 'Bonus bands reset to defaults.');
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.bonus.view')) {
            abort(403, 'You do not have permission to view performance bonus bands.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.bonus.manage')) {
            abort(403, 'You do not have permission to manage performance bonus bands.');
        }
    }
}
