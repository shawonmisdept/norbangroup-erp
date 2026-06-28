<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmModuleDashboardsTest extends TestCase
{
    use RefreshDatabase;

    private function hrmSuperAdmin(): User
    {
        $permissions = collect(config('hrm.permissions', []))
            ->flatMap(fn (array $group) => array_keys($group))
            ->values()
            ->all();

        $role = Role::create([
            'name'        => 'HRM Super Admin',
            'permissions' => $permissions,
        ]);

        return User::create([
            'name'     => 'HRM Super',
            'email'    => 'hrm-super@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    public function test_all_module_dashboards_load(): void
    {
        $admin = $this->hrmSuperAdmin();

        $routes = [
            'admin.hrm.recruitment.dashboard' => 'Recruitment Dashboard',
            'admin.hrm.employee.dashboard'    => 'Employee Dashboard',
            'admin.hrm.leave.dashboard'       => 'Leave Dashboard',
            'admin.hrm.attendance.dashboard'  => 'Attendance Dashboard',
            'admin.hrm.performance.dashboard' => 'Performance Dashboard',
            'admin.hrm.salary.dashboard'      => 'Salary Dashboard',
            'admin.hrm.compliance.dashboard'  => 'Compliance Dashboard',
            'admin.hrm.finance.dashboard'     => 'Finance Dashboard',
            'admin.hrm.rmg.dashboard'         => 'RMG Extras Dashboard',
        ];

        foreach ($routes as $route => $heading) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertSee($heading);
        }
    }
}
