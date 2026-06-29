<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Support\RolePermissionCatalog;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_grants_full_permissions_to_administrator(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = Role::where('name', 'Administrator')->firstOrFail();

        foreach (RolePermissionCatalog::administratorPermissions() as $permission) {
            $this->assertTrue(
                $admin->hasPermission($permission),
                "Administrator missing permission: {$permission}"
            );
        }
    }

    public function test_seeder_grants_transport_authority_operational_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('name', 'Transport Authority')->firstOrFail();

        foreach ([
            'tms.settings.view',
            'tms.settings.manage',
            'tms.vehicles.manage',
            'tms.drivers.manage',
            'tms.overtime.manage',
            'tms.requests.approve',
        ] as $permission) {
            $this->assertContains($permission, $role->permissions);
            $this->assertTrue($role->hasPermission($permission), "Transport Authority missing: {$permission}");
        }
    }

    public function test_seeder_grants_hr_manager_recruitment_and_approval_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('name', 'HR Manager')->firstOrFail();

        foreach ([
            'hrm.employees.manage',
            'hrm.recruitment.applications.convert',
            'hrm.employees.promotion.approve',
            'hrm.performance.approve',
        ] as $permission) {
            $this->assertTrue($role->hasPermission($permission), "HR Manager missing: {$permission}");
        }
    }

    public function test_catalog_permissions_are_registered_in_role_ui_options(): void
    {
        $options = Role::permissionOptions();

        foreach (RolePermissionCatalog::allPermissionKeys() as $permission) {
            $this->assertArrayHasKey(
                $permission,
                $options,
                "Permission missing from role UI options: {$permission}"
            );
        }
    }

    public function test_administrator_has_explicit_recruitment_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = Role::where('name', 'Administrator')->firstOrFail();

        foreach (RolePermissionCatalog::recruitmentPermissions() as $permission) {
            $this->assertContains($permission, $admin->permissions);
        }
    }
}
