<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\RolePermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tms_manage_implies_view_permission(): void
    {
        $role = Role::create([
            'name'        => 'Fleet Manager',
            'permissions' => ['tms.vehicles.manage'],
        ]);

        $this->assertTrue($role->hasPermission('tms.vehicles.manage'));
        $this->assertTrue($role->hasPermission('tms.vehicles.view'));
        $this->assertFalse($role->hasPermission('tms.drivers.view'));
    }

    public function test_tms_requests_approve_implies_view_permission(): void
    {
        $role = Role::create([
            'name'        => 'Transport Dispatcher',
            'permissions' => ['tms.requests.approve'],
        ]);

        $this->assertTrue($role->hasPermission('tms.requests.approve'));
        $this->assertTrue($role->hasPermission('tms.requests.view'));
    }

    public function test_any_tms_permission_implies_dashboard_view(): void
    {
        $role = Role::create([
            'name'        => 'Maintenance Viewer',
            'permissions' => ['tms.maintenance.view'],
        ]);

        $this->assertTrue($role->hasPermission('tms.dashboard.view'));
    }

    public function test_manage_permission_grants_tms_submodule_view(): void
    {
        $role = Role::create([
            'name'        => 'Trip Operator',
            'permissions' => ['tms.trips.manage'],
        ]);

        $user = User::create([
            'name'     => 'Trip Operator',
            'email'    => 'trip-op@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->assertTrue($user->canViewTmsSubmodule('trips'));
        $this->assertTrue($user->canViewTmsSubmodule('odometer'));
        $this->assertTrue($user->canManageTmsSubmodule('odometer'));
    }

    public function test_transport_authority_catalog_matches_config_permissions(): void
    {
        $expected = [];

        foreach (config('tms.permissions', []) as $group) {
            $expected = array_merge($expected, array_keys($group));
        }

        $expected[] = 'tms.requests.approve';

        foreach (RolePermissionCatalog::transportAuthorityPermissions() as $permission) {
            $this->assertContains($permission, $expected, "Unexpected transport permission: {$permission}");
        }
    }
}
