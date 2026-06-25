<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PERMISSIONS = [
        'hrm.recruitment.postings.view',
        'hrm.recruitment.postings.manage',
        'hrm.recruitment.applications.view',
        'hrm.recruitment.applications.manage',
        'hrm.recruitment.applications.convert',
    ];

    public function up(): void
    {
        Role::query()->each(function (Role $role) {
            $permissions = $role->permissions ?? [];

            if (in_array('hrm.employees.manage', $permissions, true)) {
                foreach ([
                    'hrm.recruitment.postings.manage',
                    'hrm.recruitment.applications.manage',
                    'hrm.recruitment.applications.convert',
                ] as $perm) {
                    if (! in_array($perm, $permissions, true)) {
                        $permissions[] = $perm;
                    }
                }
            }

            if (in_array('hrm.employees.view', $permissions, true)) {
                foreach ([
                    'hrm.recruitment.postings.view',
                    'hrm.recruitment.applications.view',
                ] as $perm) {
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
