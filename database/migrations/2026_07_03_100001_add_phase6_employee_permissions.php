<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PERMISSIONS = [
        'hrm.employees.letters.view',
        'hrm.employees.letters.manage',
        'hrm.employees.discipline.view',
        'hrm.employees.discipline.manage',
    ];

    public function up(): void
    {
        Role::query()->each(function (Role $role) {
            $permissions = $role->permissions ?? [];

            if (in_array('hrm.employees.manage', $permissions, true)) {
                foreach (['hrm.employees.letters.manage', 'hrm.employees.discipline.manage'] as $perm) {
                    if (! in_array($perm, $permissions, true)) {
                        $permissions[] = $perm;
                    }
                }
            }

            if (in_array('hrm.employees.view', $permissions, true)) {
                foreach (['hrm.employees.letters.view', 'hrm.employees.discipline.view'] as $perm) {
                    if (! in_array($perm, $permissions, true)) {
                        $permissions[] = $perm;
                    }
                }
            }

            $role->update(['permissions' => array_values(array_unique($permissions))]);
        });
    }

    public function down(): void
    {
        Role::query()->each(function (Role $role) {
            $permissions = array_values(array_filter(
                $role->permissions ?? [],
                fn (string $p) => ! in_array($p, self::PERMISSIONS, true)
            ));

            $role->update(['permissions' => $permissions]);
        });
    }
};
