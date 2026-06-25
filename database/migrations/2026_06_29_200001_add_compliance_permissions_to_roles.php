<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @return list<string> */
    private function compliancePermissions(): array
    {
        $perms = [
            'hrm.compliance.view',
            'hrm.compliance.manage',
        ];

        foreach (config('hrm.compliance_submodules', []) as $sub) {
            if (! empty($sub['permission'])) {
                $perms[] = $sub['permission'];
            }
            if (! empty($sub['manage'])) {
                $perms[] = $sub['manage'];
            }
        }

        foreach (config('hrm.leave_submodules', []) as $sub) {
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
        $extra = $this->compliancePermissions();

        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];

            if ($role->name === 'Administrator') {
                $permissions = array_values(array_unique(array_merge($permissions, $extra)));
            } elseif (in_array($role->name, ['Manager', 'HR Manager'], true)) {
                $permissions = array_values(array_unique(array_merge($permissions, [
                    'hrm.compliance.view',
                    'hrm.leave.maternity-transactions.view',
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
        $remove = $this->compliancePermissions();

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
