<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @return list<string> */
    private function permissions(): array
    {
        return [
            'hrm.attendance.manual-punch.view',
            'hrm.attendance.manual-punch.manage',
            'hrm.attendance.gate-points.view',
            'hrm.attendance.gate-points.manage',
        ];
    }

    public function up(): void
    {
        $extra = $this->permissions();

        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];

            if ($role->name === 'Administrator') {
                $permissions = array_values(array_unique(array_merge($permissions, $extra)));
            } elseif (in_array($role->name, ['Manager', 'HR Manager'], true)) {
                $permissions = array_values(array_unique(array_merge($permissions, [
                    'hrm.attendance.manual-punch.view',
                    'hrm.attendance.gate-points.view',
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
        $remove = $this->permissions();

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
    }
};
