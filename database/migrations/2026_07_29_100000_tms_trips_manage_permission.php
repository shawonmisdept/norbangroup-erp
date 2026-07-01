<?php

use App\Models\Role;
use App\Support\RolePermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permission = 'tms.trips.manage';

        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];

            if ($role->name === 'Administrator') {
                if (! in_array($permission, $permissions, true)) {
                    $permissions[] = $permission;
                }
            } elseif ($role->name === 'Transport Authority') {
                $permissions = array_values(array_unique(array_merge(
                    $permissions,
                    RolePermissionCatalog::transportAuthorityPermissions(),
                )));
            } elseif (in_array('tms.settings.manage', $permissions, true) && ! in_array($permission, $permissions, true)) {
                $permissions[] = $permission;
            }

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_unique($permissions))),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        $permission = 'tms.trips.manage';

        foreach (DB::table('roles')->get() as $role) {
            $permissions = array_values(array_filter(
                json_decode($role->permissions, true) ?? [],
                fn ($p) => $p !== $permission,
            ));

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
                'updated_at'  => now(),
            ]);
        }
    }
};
