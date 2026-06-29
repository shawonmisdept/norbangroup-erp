<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePortalAuthTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create(['name' => 'Test Factory', 'is_active' => true]);

        $this->employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => $this->factory->code . '-00001',
            'name'          => 'Portal Worker',
            'status'        => 'active',
        ]);

        EmployeePortalUser::create([
            'employee_id' => $this->employee->id,
            'password'    => 'secret-password',
            'is_active'   => true,
        ]);
    }

    public function test_employee_can_view_login_page(): void
    {
        $this->get(route('employee.login'))
            ->assertOk()
            ->assertSee('Welcome back');
    }

    public function test_employee_can_login_with_code_and_password(): void
    {
        $this->post(route('employee.login.store'), [
            'employee_code' => $this->employee->employee_code,
            'password'      => 'secret-password',
        ])
            ->assertRedirect(route('employee.dashboard'));

        $this->assertAuthenticatedAs($this->employee->portalUser, 'employee');
    }

    public function test_employee_cannot_login_with_wrong_password(): void
    {
        $this->post(route('employee.login.store'), [
            'employee_code' => $this->employee->employee_code,
            'password'      => 'wrong-password',
        ])
            ->assertSessionHasErrors('employee_code');

        $this->assertGuest('employee');
    }

    public function test_terminated_employee_cannot_login(): void
    {
        $this->employee->update(['status' => 'terminated']);

        $this->post(route('employee.login.store'), [
            'employee_code' => $this->employee->employee_code,
            'password'      => 'secret-password',
        ])
            ->assertSessionHasErrors('employee_code');

        $this->assertGuest('employee');
    }

    public function test_terminated_employee_keeps_portal_until_logout(): void
    {
        $this->employee->update(['status' => 'terminated']);

        $this->actingAs($this->employee->portalUser, 'employee')
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Portal Worker');
    }

    public function test_authenticated_employee_visiting_login_redirects_to_dashboard(): void
    {
        $this->actingAs($this->employee->portalUser, 'employee')
            ->get(route('employee.login'))
            ->assertRedirect(route('employee.dashboard'));
    }

    public function test_unlinked_portal_user_is_logged_out_from_dashboard(): void
    {
        $temp = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => $this->factory->code . '-TEMP',
            'name'          => 'Temp Worker',
            'status'        => 'active',
        ]);

        $orphan = EmployeePortalUser::create([
            'employee_id' => $temp->id,
            'password'    => 'secret-password',
            'is_active'   => true,
        ]);

        $temp->delete();

        $this->actingAs($orphan, 'employee')
            ->get(route('employee.dashboard'))
            ->assertRedirect(route('employee.login'))
            ->assertSessionHasErrors('employee_code');
    }

    public function test_authenticated_employee_can_view_dashboard(): void
    {
        $this->actingAs($this->employee->portalUser, 'employee')
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Portal Worker');
    }

    public function test_admin_web_user_cannot_access_employee_dashboard(): void
    {
        $role = Role::create([
            'name'        => 'Admin',
            'permissions' => ['orders.view'],
        ]);

        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($admin)
            ->get(route('employee.dashboard'))
            ->assertRedirect(route('employee.login'));
    }

    public function test_hr_can_enable_portal_for_employee(): void
    {
        $employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => $this->factory->code . '-00002',
            'name'          => 'No Portal Yet',
            'status'        => 'active',
        ]);

        $role = Role::create([
            'name'        => 'HR',
            'permissions' => ['hrm.employees.view', 'hrm.employees.manage'],
        ]);

        $hr = User::create([
            'name'     => 'HR',
            'email'    => 'hr@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($hr)
            ->post(route('admin.hrm.employees.portal.store', $employee), [
                'portal_password' => 'new-portal-pass',
                'portal_password_confirmation' => 'new-portal-pass',
            ])
            ->assertRedirect(route('admin.hrm.employees.show', $employee));

        $this->assertDatabaseHas('hrm_employee_portal_users', [
            'employee_id' => $employee->id,
            'is_active'   => true,
        ]);
    }
}
