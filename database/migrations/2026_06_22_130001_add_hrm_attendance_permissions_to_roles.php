<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = DB::table('roles')->get();

        foreach ($roles as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];

            if ($role->name === 'Administrator') {
                $permissions = array_values(array_unique(array_merge($permissions, [
                    'hrm.attendance.view', 'hrm.attendance.manage', 'hrm.attendance.sync',
                ])));
            } elseif ($role->name === 'Manager') {
                $permissions = array_values(array_unique(array_merge($permissions, [
                    'hrm.attendance.view',
                ])));
            }

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        $roles = DB::table('roles')->get();

        foreach ($roles as $role) {
            $permissions = array_values(array_filter(
                json_decode($role->permissions, true) ?? [],
                fn ($p) => ! in_array($p, ['hrm.attendance.view', 'hrm.attendance.manage', 'hrm.attendance.sync'], true)
            ));

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
                'updated_at'  => now(),
            ]);
        }
    }
};
