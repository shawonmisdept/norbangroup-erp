<?php

namespace App\Http\Controllers\Admin\Hrm\Concerns;

use App\Models\Factory;
use Illuminate\Http\Request;

trait ScopesHrmFactory
{
    protected function scopeToUserFactory($query, Request $request): void
    {
        if ($request->user()?->factory_id) {
            $query->where('factory_id', $request->user()->factory_id);
        }
    }

    protected function factoryOptions(Request $request): array
    {
        $query = Factory::where('is_active', true)->orderBy('name');

        if ($request->user()?->factory_id) {
            $query->where('id', $request->user()->factory_id);
        }

        return $query->pluck('name', 'id')->all();
    }

    protected function authorizeFactoryAccess(Request $request, int $factoryId): void
    {
        if ($request->user()?->factory_id && $request->user()->factory_id !== $factoryId) {
            abort(403, 'You do not have access to data for this factory / unit.');
        }
    }

    protected function scopedFactoryName(Request $request): ?string
    {
        $factoryId = $request->user()?->factory_id;

        if (! $factoryId) {
            return null;
        }

        return Factory::whereKey($factoryId)->value('name');
    }
}
