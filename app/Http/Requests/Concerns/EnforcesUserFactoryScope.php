<?php

namespace App\Http\Requests\Concerns;

trait EnforcesUserFactoryScope
{
    protected function mergeUserFactoryScope(): void
    {
        $factoryId = $this->user()?->scopedFactoryId();

        if (! $factoryId) {
            return;
        }

        $this->merge(['factory_id' => $factoryId]);
    }

    protected function userFactoryIdRule(): array
    {
        $factoryId = $this->user()?->scopedFactoryId();

        if (! $factoryId) {
            return ['required', 'exists:factories,id'];
        }

        return ['required', 'in:' . $factoryId];
    }
}
