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
        $this->seedDemoUsers();

        $this->call(MasterDataSeeder::class);

        $this->seedDemoUsers();
    }

    private function seedDemoUsers(): void
    {
        $adminRole = Role::where('name', 'Administrator')->firstOrFail();
        $managerRole = Role::where('name', 'Manager')->firstOrFail();
        $viewerRole = Role::where('name', 'Viewer')->firstOrFail();
        $hrManagerRole = Role::where('name', 'HR Manager')->firstOrFail();
        $transportRole = Role::where('name', 'Transport Authority')->firstOrFail();

        $defaultFactoryId = Factory::query()->where('is_active', true)->orderBy('id')->value('id');

        User::updateOrCreate(
            ['email' => 'admin@norbangroup.com'],
            ['name' => 'Admin', 'password' => 'password', 'role_id' => $adminRole->id, 'factory_id' => null]
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
