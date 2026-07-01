<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ManagementUserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('name', 'Management')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'mansifsiddiqui@gmail.com'],
            [
                'name'       => 'Mansif Siddiqui',
                'password'   => 'password',
                'role_id'    => $role->id,
                'factory_id' => null,
            ]
        );

        $this->command?->info('Management user ready: mansifsiddiqui@gmail.com (role: Management, all units)');
    }
}
