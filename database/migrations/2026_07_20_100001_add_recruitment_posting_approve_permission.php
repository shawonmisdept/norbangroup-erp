<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PERMISSION = 'hrm.recruitment.postings.approve';

    public function up(): void
    {
        Role::query()->each(function (Role $role) {
            $permissions = $role->permissions ?? [];

            if (in_array('hrm.recruitment.postings.manage', $permissions, true)
                && ! in_array(self::PERMISSION, $permissions, true)) {
                $permissions[] = self::PERMISSION;
                $role->update(['permissions' => array_values(array_unique($permissions))]);
            }
        });
    }

    public function down(): void
    {
        Role::query()->each(function (Role $role) {
            $permissions = array_values(array_filter(
                $role->permissions ?? [],
                fn (string $p) => $p !== self::PERMISSION
            ));

            $role->update(['permissions' => $permissions]);
        });
    }
};
