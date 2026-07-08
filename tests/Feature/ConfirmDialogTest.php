<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_include_confirm_dialog_markup(): void
    {
        $role = Role::create([
            'name'        => 'Dialog Viewer',
            'permissions' => ['hrm.salary.close.view', 'hrm.salary.process.view'],
        ]);

        $user = User::create([
            'name'     => 'Dialog Viewer',
            'email'    => 'dialog-viewer@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.salary.close.index'))
            ->assertOk()
            ->assertSee('confirmDialog()', false);
    }

    public function test_salary_process_calculate_form_uses_confirm_dialog_attributes(): void
    {
        $role = Role::create([
            'name'        => 'Salary Processor',
            'permissions' => ['hrm.salary.process.view', 'hrm.salary.process.manage'],
        ]);

        $user = User::create([
            'name'     => 'Salary Processor',
            'email'    => 'salary-processor@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.salary.process.index'))
            ->assertOk()
            ->assertSee('data-confirm="Calculate payroll for the selected factory and month?', false);
    }
}
