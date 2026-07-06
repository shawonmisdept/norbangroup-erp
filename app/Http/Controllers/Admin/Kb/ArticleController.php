<?php

namespace App\Http\Controllers\Admin\Kb;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Services\KbAccessService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function show(Request $request, string $code, string $submodule, KbAccessService $access)
    {
        $module = $access->findModuleByCode($code);

        if (! $module || ! $module->is_active) {
            abort(404);
        }

        if (! $access->canViewModule($request->user(), $module)) {
            abort(403);
        }

        $submoduleKey = $submodule === 'overview' ? null : $submodule;

        $article = KbArticle::query()
            ->where('kb_module_id', $module->id)
            ->when($submoduleKey === null, function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('submodule_key')->orWhere('submodule_key', '');
                });
            }, fn ($q) => $q->where('submodule_key', $submoduleKey))
            ->with('updatedBy')
            ->first();

        if (! $article || ! $access->canViewArticle($request->user(), $article)) {
            abort(404);
        }

        $submodules = $module->submoduleDefinitions();
        $submoduleLabel = $submoduleKey === null
            ? 'Overview'
            : ($submodules[$submoduleKey]['label'] ?? $submoduleKey);

        return view('admin.kb.article', [
            'module'         => $module,
            'article'        => $article,
            'submoduleKey'   => $submodule,
            'submoduleLabel' => $submoduleLabel,
            'canManage'      => $access->canManageKb($request->user()),
        ]);
    }
}
