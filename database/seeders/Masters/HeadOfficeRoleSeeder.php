<?php

namespace Database\Seeders\Masters;

use App\Models\Role;
use App\Support\HeadOfficeRolePermissionCatalog;
use Illuminate\Database\Seeder;

class HeadOfficeRoleSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{roles: list<string>} $data */
        $data = require database_path('seeders/data/head_office_org.php');

        $synced = 0;

        foreach ($data['roles'] as $roleName) {
            $roleName = trim($roleName);

            if ($roleName === '' || $roleName === 'No Need') {
                continue;
            }

            $permissions = HeadOfficeRolePermissionCatalog::permissionsFor($roleName);

            Role::updateOrCreate(
                ['name' => $roleName],
                ['permissions' => $permissions]
            );

            $synced++;
        }

        $this->command?->info("Synced {$synced} Head Office role(s) with permissions.");
    }
}
