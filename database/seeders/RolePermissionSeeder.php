<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\RolePermissionCatalog;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->syncAdministrator();
        $this->syncManagement();
        $this->syncManager();
        $this->syncViewer();
        $this->syncHrManager();
        $this->syncTransportAuthority();

        $this->command?->info('Synced role permissions for Administrator, Management, Manager, Viewer, HR Manager, and Transport Authority.');
    }

    private function syncManagement(): void
    {
        Role::updateOrCreate(
            ['name' => 'Management'],
            ['permissions' => RolePermissionCatalog::administratorPermissions()]
        );
    }

    private function syncAdministrator(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'Administrator'],
            ['permissions' => []]
        );

        $role->update([
            'permissions' => RolePermissionCatalog::administratorPermissions(),
        ]);
    }

    private function syncManager(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'Manager'],
            ['permissions' => []]
        );

        $role->update([
            'permissions' => array_values(array_unique(array_merge(
                $role->permissions ?? [],
                RolePermissionCatalog::managerPermissions()
            ))),
        ]);
    }

    private function syncViewer(): void
    {
        Role::updateOrCreate(
            ['name' => 'Viewer'],
            ['permissions' => RolePermissionCatalog::viewerPermissions()]
        );
    }

    private function syncHrManager(): void
    {
        Role::updateOrCreate(
            ['name' => 'HR Manager'],
            ['permissions' => RolePermissionCatalog::hrManagerPermissions()]
        );
    }

    private function syncTransportAuthority(): void
    {
        Role::updateOrCreate(
            ['name' => 'Transport Authority'],
            ['permissions' => RolePermissionCatalog::transportAuthorityPermissions()]
        );
    }
}
