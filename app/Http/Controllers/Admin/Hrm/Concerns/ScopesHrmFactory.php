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
            abort(403);
        }
    }
}
