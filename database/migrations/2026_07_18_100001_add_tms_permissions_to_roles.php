<?php

use App\Models\Role;
use App\Support\RolePermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @return list<string> */
    private function tmsPermissions(): array
    {
        $perms = [];

        foreach (config('tms.permissions', []) as $group) {
            $perms = array_merge($perms, array_keys($group));
        }

        foreach (config('tms.submodules', []) as $sub) {
            if (($sub['status'] ?? '') !== 'active') {
                continue;
            }
            if (! empty($sub['permission'])) {
                $perms[] = $sub['permission'];
            }
            if (! empty($sub['manage'])) {
                $perms[] = $sub['manage'];
            }
        }

        return array_values(array_unique($perms));
    }

    public function up(): void
    {
        $all = $this->tmsPermissions();
        $authority = RolePermissionCatalog::transportAuthorityPermissions();

        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];

            if ($role->name === 'Administrator') {
                $permissions = array_values(array_unique(array_merge($permissions, $all)));
            } elseif ($role->name === 'Transport Authority') {
                $permissions = array_values(array_unique(array_merge($permissions, $authority)));
            }

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
                'updated_at'  => now(),
            ]);
        }

        if (! DB::table('roles')->where('name', 'Transport Authority')->exists()) {
            DB::table('roles')->insert([
                'name'        => 'Transport Authority',
                'permissions' => json_encode($authority),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        $remove = $this->tmsPermissions();

        foreach (DB::table('roles')->get() as $role) {
            $permissions = array_values(array_filter(
                json_decode($role->permissions, true) ?? [],
                fn ($p) => ! in_array($p, $remove, true)
            ));

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
                'updated_at'  => now(),
            ]);
        }

        DB::table('roles')->where('name', 'Transport Authority')->delete();
    }
};
