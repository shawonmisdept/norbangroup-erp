<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalarySidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_salary_view_shows_all_salary_sidebar_modules(): void
    {
        $role = Role::create([
            'name'        => 'Salary Viewer',
            'permissions' => ['hrm.salary.view'],
        ]);

        $user = User::create([
            'name'     => 'Salary Viewer',
            'email'    => 'salary-viewer@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.salary.hub'))
            ->assertOk()
            ->assertSee('Salary Banks', false)
            ->assertSee('Bank Ledger', false)
            ->assertSee('Dashboard', false);
    }

    public function test_banks_index_requires_banks_view_not_heads_view(): void
    {
        $banksOnly = Role::create([
            'name'        => 'Banks Only',
            'permissions' => ['hrm.salary.banks.view'],
        ]);

        $user = User::create([
            'name'     => 'Banks Only',
            'email'    => 'banks-only@test.com',
            'password' => 'password',
            'role_id'  => $banksOnly->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.salary.banks.index'))
            ->assertOk();
    }
}
