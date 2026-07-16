<?php

namespace Database\Seeders;

use App\Models\Factory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AppSettingSeeder::class);
        $this->call(RolePermissionSeeder::class);
        $this->call(KbSeeder::class);
        $this->seedDemoUsers();

        $this->call(MasterDataSeeder::class);
    }

    private function seedDemoUsers(): void
    {
        $roles = Role::query()
            ->whereIn('name', ['Administrator', 'Management', 'Manager', 'Viewer', 'HR Manager', 'Transport Authority'])
            ->get()
            ->keyBy('name');

        $adminRole = $roles['Administrator'] ?? throw new \RuntimeException('Administrator role missing.');
        $managementRole = $roles['Management'] ?? throw new \RuntimeException('Management role missing.');
        $managerRole = $roles['Manager'] ?? throw new \RuntimeException('Manager role missing.');
        $viewerRole = $roles['Viewer'] ?? throw new \RuntimeException('Viewer role missing.');
        $hrManagerRole = $roles['HR Manager'] ?? throw new \RuntimeException('HR Manager role missing.');
        $transportRole = $roles['Transport Authority'] ?? throw new \RuntimeException('Transport Authority role missing.');

        $defaultFactoryId = Factory::query()->where('is_active', true)->orderBy('id')->value('id');

        User::updateOrCreate(
            ['email' => 'admin@norbangroup.com'],
            ['name' => 'Admin', 'password' => 'password', 'role_id' => $adminRole->id, 'factory_id' => null]
        );

        User::updateOrCreate(
            ['email' => 'mansifsiddiqui@gmail.com'],
            [
                'name'       => 'Mansif Siddiqui',
                'password'   => 'password',
                'role_id'    => $managementRole->id,
                'factory_id' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@norbangroup.com'],
            ['name' => 'Manager', 'password' => 'password', 'role_id' => $managerRole->id, 'factory_id' => $defaultFactoryId]
        );

        User::updateOrCreate(
            ['email' => 'viewer@norbangroup.com'],
            ['name' => 'Viewer', 'password' => 'password', 'role_id' => $viewerRole->id, 'factory_id' => null]
        );

        User::updateOrCreate(
            ['email' => 'hr-manager@test.com'],
            ['name' => 'HR Manager', 'password' => 'password', 'role_id' => $hrManagerRole->id, 'factory_id' => $defaultFactoryId]
        );

        User::updateOrCreate(
            ['email' => 'transport@test.com'],
            ['name' => 'Transport Authority', 'password' => 'password', 'role_id' => $transportRole->id, 'factory_id' => $defaultFactoryId]
        );
    }
}
