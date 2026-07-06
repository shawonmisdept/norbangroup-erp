<?php

namespace App\Http\Controllers\Admin\Kb;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Services\KbAccessService;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function show(Request $request, string $code, KbAccessService $access)
    {
        $module = $access->findModuleByCode($code);

        if (! $module || ! $module->is_active) {
            abort(404);
        }

        if (! $access->canViewModule($request->user(), $module)) {
            abort(403);
        }

        $canManage = $access->canManageKb($request->user());

        $articles = KbArticle::query()
            ->where('kb_module_id', $module->id)
            ->when(! $canManage, fn ($q) => $q->where('is_published', true))
            ->get()
            ->keyBy(fn (KbArticle $article) => $article->isOverview() ? 'overview' : $article->submodule_key);

        $submodules = collect($module->submoduleDefinitions())
            ->filter(fn ($sub) => ($sub['status'] ?? 'active') !== 'planned');

        return view('admin.kb.module', [
            'module'     => $module,
            'submodules' => $submodules,
            'articles'   => $articles,
            'canManage'  => $canManage,
        ]);
    }
}
