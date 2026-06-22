<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MasterDataSeeder::class);
        $this->call(AppSettingSeeder::class);

        $adminRole = Role::where('name', 'Administrator')->first();
        $managerRole = Role::where('name', 'Manager')->first();
        $viewerRole = Role::where('name', 'Viewer')->first();

        User::updateOrCreate(
            ['email' => 'admin@norbangroup.com'],
            ['name' => 'Admin', 'password' => 'password', 'role_id' => $adminRole->id]
        );

        User::updateOrCreate(
            ['email' => 'manager@norbangroup.com'],
            ['name' => 'Manager', 'password' => 'password', 'role_id' => $managerRole->id]
        );

        User::updateOrCreate(
            ['email' => 'viewer@norbangroup.com'],
            ['name' => 'Viewer', 'password' => 'password', 'role_id' => $viewerRole->id]
        );
    }
}
