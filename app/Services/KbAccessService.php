<?php

namespace App\Services;

use App\Models\KbArticle;
use App\Models\KbModule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class KbAccessService
{
    public function canViewKb(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasPermission('kb.manage') || $user->hasPermission('kb.view')) {
            return true;
        }

        return $user->hasAnyHrmViewPermission()
            || $user->hasPermission('orders.view')
            || $user->hasAnyTmsViewPermission();
    }

    public function canManageKb(?User $user): bool
    {
        return $user?->hasPermission('kb.manage') ?? false;
    }

    public function canViewModule(?User $user, KbModule $module): bool
    {
        if (! $this->canViewKb($user)) {
            return false;
        }

        if ($this->canManageKb($user)) {
            return true;
        }

        if (! $module->view_permission) {
            return true;
        }

        return $user->hasPermission($module->view_permission);
    }

    public function canViewArticle(?User $user, KbArticle $article): bool
    {
        if (! $this->canViewModule($user, $article->module)) {
            return false;
        }

        if ($this->canManageKb($user)) {
            return true;
        }

        return $article->is_published;
    }

    /** @return Collection<int, KbModule> */
    public function visibleModulesFor(?User $user): Collection
    {
        return KbModule::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label_en')
            ->get()
            ->filter(fn (KbModule $module) => $this->canViewModule($user, $module))
            ->values();
    }

    public function findModuleByCode(string $code): ?KbModule
    {
        return KbModule::query()->where('code', $code)->first();
    }

    public function findPublishedArticle(KbModule $module, ?string $submoduleKey): ?KbArticle
    {
        $query = KbArticle::query()->where('kb_module_id', $module->id);

        if ($submoduleKey === null || $submoduleKey === 'overview') {
            $query->where(function ($q) {
                $q->whereNull('submodule_key')->orWhere('submodule_key', '');
            });
        } else {
            $query->where('submodule_key', $submoduleKey);
        }

        return $query->first();
    }
}
