<?php

namespace App\Services\Hrm\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopesDashboardFactory
{
    protected function scopeFactoryQuery(Builder $query, User $user, ?int $factoryId, string $column = 'factory_id'): Builder
    {
        if ($factoryId) {
            return $query->where($column, $factoryId);
        }

        if ($user->factory_id) {
            return $query->where($column, $user->factory_id);
        }

        return $query;
    }
}
