<?php

namespace App\Http\Controllers\Admin\Hrm\Concerns;

use App\Models\Factory;
use Illuminate\Http\Request;

trait ScopesHrmFactory
{
    protected function scopeToUserFactory($query, Request $request): void
    {
        $factoryId = $request->user()?->scopedFactoryId();

        if ($factoryId) {
            $query->where($query->getModel()->getTable() . '.factory_id', $factoryId);
        }
    }

    protected function factoryOptions(Request $request): array
    {
        $query = Factory::where('is_active', true)->orderBy('name');

        $factoryId = $request->user()?->scopedFactoryId();

        if ($factoryId) {
            $query->where('id', $factoryId);
        }

        return $query->pluck('name', 'id')->all();
    }

    protected function authorizeFactoryAccess(Request $request, mixed $factoryId): void
    {
        if ($factoryId === null || $factoryId === '') {
            return;
        }

        if (! $request->user()?->canAccessFactory((int) $factoryId)) {
            abort(403, 'You do not have access to data for this factory / unit.');
        }
    }

    protected function scopedFactoryName(Request $request): ?string
    {
        $factoryId = $request->user()?->scopedFactoryId();

        if (! $factoryId) {
            return null;
        }

        return Factory::whereKey($factoryId)->value('name');
    }

    /** Resolve factory filter for dashboards, reports, and exports. */
    protected function resolveFactoryFilter(Request $request, ?int $requested = null): ?int
    {
        $factoryId = $request->user()?->resolveFactoryFilter($requested);

        if ($factoryId && ! $request->user()?->isUnitScoped()) {
            $this->authorizeFactoryAccess($request, $factoryId);
        }

        return $factoryId;
    }

    /** Resolve factory filter defaulting to the first available unit. */
    protected function resolveFactoryFilterFromRequest(Request $request, ?array $factories = null): int
    {
        $factories ??= $this->factoryOptions($request);

        $requested = $request->filled('factory_id')
            ? (int) $request->factory_id
            : ($factories !== [] ? (int) array_key_first($factories) : null);

        return $this->resolveFactoryFilter($request, $requested) ?? 0;
    }

    /** @return int Factory id or 422 when none could be resolved. */
    protected function requireFactoryFilter(Request $request, ?int $requested = null): int
    {
        $factoryId = $this->resolveFactoryFilter($request, $requested);

        if (! $factoryId) {
            $options = $this->factoryOptions($request);

            if (count($options) === 1) {
                return (int) array_key_first($options);
            }

            abort(422, 'Select a factory / unit.');
        }

        return $factoryId;
    }
}
