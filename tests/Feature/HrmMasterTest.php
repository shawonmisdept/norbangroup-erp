<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\WorkerCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmMasterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name'        => 'HRM Admin',
            'permissions' => ['hrm.masters.view', 'hrm.masters.manage'],
        ]);

        $this->admin = User::create([
            'name'     => 'HRM Admin',
            'email'    => 'hrm-admin@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    public function test_hrm_master_hub_is_accessible_with_permission(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.hrm.masters.hub'))
            ->assertOk()
            ->assertSee('HRM Master Data Registry')
            ->assertSee('Buildings')
            ->assertSee('Worker Categories');
    }

    public function test_admin_can_create_hrm_worker_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.hrm.masters.store', 'hrm-worker-categories'), [
                'name'      => 'Operator',
                'is_active' => 1,
            ]);

        $category = WorkerCategory::first();
        $this->assertNotNull($category);

        $response->assertRedirect(route('admin.hrm.masters.show', ['hrm-worker-categories', $category]));
        $this->assertSame('Operator', $category->name);
    }

    public function test_admin_can_create_hrm_building_for_factory(): void
    {
        $factory = Factory::create([
            'name'      => 'Unit 1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.hrm.masters.store', 'hrm-buildings'), [
                'factory_id' => $factory->id,
                'name'       => 'Building A',
                'is_active'  => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('hrm_buildings', [
            'factory_id' => $factory->id,
            'name'       => 'Building A',
        ]);
    }

    public function test_user_without_hrm_permission_cannot_access_hub(): void
    {
        $role = Role::create([
            'name'        => 'No HRM',
            'permissions' => ['orders.view'],
        ]);

        $user = User::create([
            'name'     => 'No Access',
            'email'    => 'no-hrm@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.masters.hub'))
            ->assertForbidden();
    }
}
