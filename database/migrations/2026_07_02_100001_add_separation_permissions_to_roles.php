<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PERMISSIONS = [
        'hrm.employees.separation.view',
        'hrm.employees.separation.manage',
        'hrm.employees.separation.approve',
    ];

    public function up(): void
    {
        Role::query()->each(function (Role $role) {
            $permissions = $role->permissions ?? [];

            if (in_array('hrm.employees.manage', $permissions, true) && ! in_array('hrm.employees.separation.manage', $permissions, true)) {
                $permissions[] = 'hrm.employees.separation.manage';
            }

            if (in_array('hrm.employees.view', $permissions, true) && ! in_array('hrm.employees.separation.view', $permissions, true)) {
                $permissions[] = 'hrm.employees.separation.view';
            }

            if (in_array('hrm.employees.manage', $permissions, true) && ! in_array('hrm.employees.separation.approve', $permissions, true)) {
                $permissions[] = 'hrm.employees.separation.approve';
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
