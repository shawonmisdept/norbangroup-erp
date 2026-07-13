<?php

namespace App\Services\Tms;

use App\Models\User;
use Illuminate\Http\Request;

class TmsDashboardService
{
    /** @return array<int, array<string, mixed>> */
    public function quickActions(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return [];
        }

        return collect(config('tms.dashboard_quick_actions', []))
            ->filter(fn (array $action) => $this->userCanSeeQuickAction($user, $action))
            ->map(fn (array $action) => [
                'label' => $action['label'],
                'url'   => route($action['route'], $action['params'] ?? []),
            ])
            ->values()
            ->all();
    }

    /** @param  array<string, mixed>  $action */
    private function userCanSeeQuickAction(User $user, array $action): bool
    {
        if (empty($action['submodule']) || empty($action['manage'])) {
            return false;
        }

        return $user->canManageTmsSubmodule($action['submodule']);
    }
}
