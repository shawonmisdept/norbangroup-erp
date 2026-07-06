<?php

namespace App\Http\Controllers\Admin\Kb;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Kb\StoreKbArticleRequest;
use App\Models\KbArticle;
use App\Models\KbModule;
use App\Services\KbAccessService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ManageController extends Controller
{
    /** @param Collection<int, KbModule> $modules */
    private function submoduleOptionsFor(Collection $modules): array
    {
        $options = [];

        foreach ($modules as $module) {
            $options[$module->id] = collect($module->submoduleDefinitions())
                ->map(fn (array $sub, string $key) => ['key' => $key, 'label' => $sub['label'] ?? $key])
                ->values()
                ->all();
        }

        return $options;
    }

    /** @return array<string, mixed> */
    private function formViewData(KbArticle $article, Collection $modules, ?KbModule $selectedModule): array
    {
        return [
            'article'          => $article,
            'modules'          => $modules,
            'selectedModule'   => $selectedModule,
            'submoduleOptions' => $this->submoduleOptionsFor($modules),
        ];
    }

    public function index(Request $request)
    {
        $articles = KbArticle::query()
            ->with('module')
            ->orderBy('kb_module_id')
            ->orderBy('submodule_key')
            ->paginate(30);

        return view('admin.kb.manage.index', [
            'articles' => $articles,
        ]);
    }

    public function create(KbAccessService $access)
    {
        $modules = $access->visibleModulesFor(auth()->user());

        return view('admin.kb.manage.form', $this->formViewData(
            new KbArticle(['is_published' => false]),
            $modules,
            null,
        ));
    }

    public function store(StoreKbArticleRequest $request)
    {
        $article = KbArticle::query()->create([
            ...$request->validated(),
            'is_published'       => $request->boolean('is_published'),
            'updated_by_user_id' => $request->user()->id,
        ]);
        $article->load('module');

        return redirect()
            ->route('admin.kb.article', [
                'code'      => $article->module->code,
                'submodule' => $article->routeKey(),
            ])
            ->with('success', 'Article created.');
    }

    public function edit(KbArticle $article, KbAccessService $access)
    {
        $article->load('module');

        $modules = KbModule::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.kb.manage.form', $this->formViewData(
            $article,
            $modules,
            $article->module,
        ));
    }

    public function update(StoreKbArticleRequest $request, KbArticle $article)
    {
        $article->update([
            ...$request->validated(),
            'is_published'       => $request->boolean('is_published'),
            'updated_by_user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.kb.article', [
                'code'      => $article->module->code,
                'submodule' => $article->routeKey(),
            ])
            ->with('success', 'Article updated.');
    }
}
