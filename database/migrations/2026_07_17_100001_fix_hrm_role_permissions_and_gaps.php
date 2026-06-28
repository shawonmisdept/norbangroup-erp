<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PROMOTION_PERMISSIONS = [
        'hrm.employees.promotion.view',
        'hrm.employees.promotion.manage',
        'hrm.employees.promotion.approve',
    ];

    /** @return list<string> */
    private function rmgPermissions(): array
    {
        $perms = [
            'hrm.rmg.view',
            'hrm.rmg.manage',
        ];

        foreach (config('hrm.rmg_submodules', []) as $sub) {
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

    /** @return list<string> */
    private function hrManagerExtras(): array
    {
        return [
            'hrm.employees.manage',
            'hrm.leave.manage',
            'hrm.leave.approve',
            'hrm.employees.separation.view',
            'hrm.employees.separation.manage',
            'hrm.employees.separation.approve',
            'hrm.employees.promotion.view',
            'hrm.employees.promotion.manage',
            'hrm.employees.promotion.approve',
            'hrm.recruitment.postings.manage',
            'hrm.recruitment.applications.manage',
            'hrm.recruitment.applications.convert',
            'hrm.performance.approve',
        ];
    }

    public function up(): void
    {
        $rmgPermissions = $this->rmgPermissions();

        if (! Role::query()->where('name', 'HR Manager')->exists()) {
            $manager = Role::query()->where('name', 'Manager')->first();
            $basePermissions = $manager?->permissions ?? [];

            Role::create([
                'name'        => 'HR Manager',
                'permissions' => array_values(array_unique(array_merge($basePermissions, $this->hrManagerExtras()))),
            ]);
        }

        Role::query()->each(function (Role $role) use ($rmgPermissions) {
            $permissions = $role->permissions ?? [];

            if ($role->name === 'Administrator') {
                $permissions = array_values(array_unique(array_merge($permissions, $rmgPermissions, self::PROMOTION_PERMISSIONS)));
            }

            if (in_array('hrm.employees.manage', $permissions, true)) {
                foreach (['hrm.employees.promotion.manage', 'hrm.employees.promotion.approve'] as $perm) {
                    if (! in_array($perm, $permissions, true)) {
                        $permissions[] = $perm;
                    }
                }
            }

            if (in_array('hrm.employees.view', $permissions, true) && ! in_array('hrm.employees.promotion.view', $permissions, true)) {
                $permissions[] = 'hrm.employees.promotion.view';
            }

            if ($role->name === 'HR Manager') {
                $permissions = array_values(array_unique(array_merge($permissions, $this->hrManagerExtras())));
            }

            $role->update(['permissions' => array_values(array_unique($permissions))]);
        });
    }

    public function down(): void
    {
        $remove = array_merge($this->rmgPermissions(), self::PROMOTION_PERMISSIONS);

        Role::query()->where('name', 'HR Manager')->delete();

        Role::query()->each(function (Role $role) use ($remove) {
            $permissions = array_values(array_filter(
                $role->permissions ?? [],
                fn (string $p) => ! in_array($p, $remove, true)
            ));

            $role->update(['permissions' => $permissions]);
        });
    }
};
