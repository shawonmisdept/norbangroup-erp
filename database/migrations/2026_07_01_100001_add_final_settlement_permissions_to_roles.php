<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @return list<string> */
    private function settlementPermissions(): array
    {
        return [
            'hrm.finance.settlement.view',
            'hrm.finance.settlement.manage',
        ];
    }

    public function up(): void
    {
        $extra = $this->settlementPermissions();

        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];

            if ($role->name === 'Administrator') {
                $permissions = array_values(array_unique(array_merge($permissions, $extra)));
            } elseif (in_array($role->name, ['Manager', 'HR Manager'], true)) {
                $permissions = array_values(array_unique(array_merge($permissions, ['hrm.finance.settlement.view'])));
            }

            if (in_array('hrm.finance.manage', $permissions, true) && ! in_array('hrm.finance.settlement.manage', $permissions, true)) {
                $permissions[] = 'hrm.finance.settlement.manage';
            }

            if (in_array('hrm.finance.view', $permissions, true) && ! in_array('hrm.finance.settlement.view', $permissions, true)) {
                $permissions[] = 'hrm.finance.settlement.view';
            }

            $permissions = array_values(array_unique($permissions));

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        $remove = $this->settlementPermissions();

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
