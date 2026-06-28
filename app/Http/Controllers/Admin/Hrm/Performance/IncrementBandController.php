<?php

namespace App\Http\Controllers\Admin\Hrm\Performance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\PerformanceIncrementBand;
use App\Services\Hrm\PerformanceIncrementBandService;
use Illuminate\Http\Request;

class IncrementBandController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private PerformanceIncrementBandService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $factoryId = $request->integer('factory_id') ?: $request->user()?->factory_id;

        if (! $factoryId && count($this->factoryOptions($request)) === 1) {
            $factoryId = (int) array_key_first($this->factoryOptions($request));
        }

        $bands = $factoryId
            ? PerformanceIncrementBand::query()->where('factory_id', $factoryId)->orderByDesc('min_score')->get()
            : collect();

        if ($factoryId && $bands->isEmpty()) {
            $bands = $this->service->bandsForFactory($factoryId);
        }

        return view('admin.hrm.performance.increment-bands.index', [
            'bands'             => $bands,
            'factories'         => $this->factoryOptions($request),
            'scopedFactoryName' => $this->scopedFactoryName($request),
            'factoryId'         => $factoryId,
            'canManage'         => $request->user()?->hasPermission('hrm.performance.increment.manage') ?? false,
        ]);
    }

    public function edit(Request $request)
    {
        $this->ensureCanManage($request);

        $factoryId = $request->integer('factory_id') ?: $request->user()?->factory_id;

        if (! $factoryId) {
            abort(422, 'Select a factory to edit increment bands.');
        }

        $this->authorizeFactoryAccess($request, $factoryId);

        $bands = PerformanceIncrementBand::query()
            ->where('factory_id', $factoryId)
            ->orderByDesc('min_score')
            ->get();

        if ($bands->isEmpty()) {
            $bands = $this->service->bandsForFactory($factoryId);
        }

        return view('admin.hrm.performance.increment-bands.form', [
            'factoryId'   => $factoryId,
            'factoryName' => $this->factoryOptions($request)[$factoryId] ?? 'Factory',
            'canManage'   => true,
            'bands'       => $bands->map(fn (PerformanceIncrementBand $b) => [
                'name'              => $b->name,
                'min_score'         => $b->min_score,
                'max_score'         => $b->max_score,
                'increment_percent' => $b->increment_percent,
                'sort_order'        => $b->sort_order,
                'is_active'         => $b->is_active,
            ])->values()->all(),
        ]);
    }

    public function update(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'factory_id'                    => ['required', 'exists:factories,id'],
            'bands'                         => ['required', 'array', 'min:1'],
            'bands.*.name'                  => ['required', 'string', 'max:100'],
            'bands.*.min_score'             => ['required', 'numeric', 'min:0', 'max:100'],
            'bands.*.max_score'             => ['required', 'numeric', 'min:0', 'max:100'],
            'bands.*.increment_percent'     => ['required', 'numeric', 'min:0', 'max:100'],
            'bands.*.sort_order'            => ['nullable', 'integer', 'min:0'],
            'bands.*.is_active'             => ['nullable', 'boolean'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        $this->service->syncFactoryBands((int) $validated['factory_id'], $validated['bands']);

        return redirect()->route('admin.hrm.performance.increment-bands.index', ['factory_id' => $validated['factory_id']])
            ->with('success', 'Increment bands updated.');
    }

    public function reset(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate(['factory_id' => ['required', 'exists:factories,id']]);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        PerformanceIncrementBand::query()->where('factory_id', $validated['factory_id'])->delete();
        $this->service->seedDefaultBands((int) $validated['factory_id']);

        return back()->with('success', 'Increment bands reset to defaults.');
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.increment.view')) {
            abort(403, 'You do not have permission to view increment bands.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.increment.manage')) {
            abort(403, 'You do not have permission to manage increment bands.');
        }
    }
}
