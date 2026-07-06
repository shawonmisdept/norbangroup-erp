<?php

namespace Database\Seeders;

use App\Models\KbModule;
use Illuminate\Database\Seeder;

class KbModuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('knowledge-base.modules', []) as $row) {
            KbModule::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'label_en'          => $row['label_en'],
                    'label_bn'          => $row['label_bn'],
                    'view_permission'   => $row['view_permission'] ?? null,
                    'submodules_config' => $row['submodules_config'] ?? null,
                    'sort_order'        => $row['sort_order'] ?? 0,
                    'is_active'         => true,
                ],
            );
        }
    }
}
