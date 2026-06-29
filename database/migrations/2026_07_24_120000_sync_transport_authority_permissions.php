<?php

use App\Models\Role;
use App\Support\RolePermissionCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = RolePermissionCatalog::transportAuthorityPermissions();

        if (Role::query()->where('name', 'Transport Authority')->exists()) {
            Role::query()->where('name', 'Transport Authority')->update([
                'permissions' => $permissions,
            ]);
        } else {
            Role::create([
                'name'        => 'Transport Authority',
                'permissions' => $permissions,
            ]);
        }
    }

    public function down(): void
    {
        // Preserved intentionally — role permissions are managed by RolePermissionSeeder.
    }
};
