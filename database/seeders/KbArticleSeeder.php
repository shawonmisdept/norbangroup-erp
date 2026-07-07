<?php

namespace Database\Seeders;

use App\Models\KbArticle;
use App\Models\KbModule;
use App\Support\KbArticleSeedBuilder;
use Illuminate\Database\Seeder;

class KbArticleSeeder extends Seeder
{
    public function run(): void
    {
        $builder = new KbArticleSeedBuilder;
        $count = 0;

        $modules = KbModule::query()->where('is_active', true)->orderBy('sort_order')->get();

        if ($modules->isEmpty()) {
            $this->command?->warn('No KB modules found — run KbModuleSeeder first.');

            return;
        }

        foreach ($modules as $module) {
            $this->seedArticle($module, $builder->overviewArticle($module));
            $count++;

            foreach ($module->submoduleDefinitions() as $key => $sub) {
                if (($sub['status'] ?? 'active') === 'planned') {
                    continue;
                }

                $this->seedArticle($module, $builder->submoduleArticle($module, $key, $sub));
                $count++;
            }
        }

        $this->command?->info("Seeded {$count} knowledge base articles across {$modules->count()} modules.");
    }

    /** @param array<string, mixed> $payload */
    private function seedArticle(KbModule $module, array $payload): void
    {
        KbArticle::query()->updateOrCreate(
            [
                'kb_module_id'  => $module->id,
                'submodule_key' => $payload['submodule_key'],
            ],
            [
                'title_en'           => $payload['title_en'],
                'title_bn'           => $payload['title_bn'],
                'summary_en'         => $payload['summary_en'],
                'summary_bn'         => $payload['summary_bn'],
                'purpose_en'         => $payload['purpose_en'],
                'purpose_bn'         => $payload['purpose_bn'],
                'audience_en'        => $payload['audience_en'],
                'audience_bn'        => $payload['audience_bn'],
                'usage_rules_en'     => $payload['usage_rules_en'],
                'usage_rules_bn'     => $payload['usage_rules_bn'],
                'body_en'            => $payload['body_en'] ?? null,
                'body_bn'            => $payload['body_bn'] ?? null,
                'is_published'       => $payload['is_published'],
                'updated_by_user_id' => null,
            ],
        );
    }
}
