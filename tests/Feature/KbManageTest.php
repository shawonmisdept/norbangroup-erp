<?php

namespace Tests\Feature;

use App\Models\KbArticle;
use App\Models\KbModule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KbManageTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_index_loads_for_kb_manager(): void
    {
        $role = Role::query()->create([
            'name' => 'KB Admin',
            'permissions' => ['kb.manage', 'kb.view'],
        ]);

        $user = User::query()->create([
            'name' => 'KB Manager',
            'email' => 'kb-manager@test.local',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);

        $module = KbModule::query()->create([
            'code' => 'test-module',
            'label_en' => 'Test Module',
            'label_bn' => 'Test',
            'view_permission' => 'kb.view',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        KbArticle::query()->create([
            'kb_module_id' => $module->id,
            'submodule_key' => null,
            'title_en' => 'Test "Article" Guide',
            'title_bn' => 'Test',
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.kb.manage.index'))
            ->assertOk();
    }
}
