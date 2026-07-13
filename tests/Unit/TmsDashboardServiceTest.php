<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use App\Services\Tms\TmsDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TmsDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_only_users_see_no_create_quick_actions(): void
    {
        $role = Role::create([
            'name'        => 'TMS Viewer',
            'permissions' => [
                'tms.dashboard.view',
                'tms.requests.view',
                'tms.trips.view',
                'tms.fuel.view',
            ],
        ]);

        $user = User::create([
            'name'     => 'Viewer',
            'email'    => 'viewer@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->assertSame([], (new TmsDashboardService)->quickActions($request));
    }

    public function test_manage_permission_shows_matching_create_actions(): void
    {
        $role = Role::create([
            'name'        => 'Fuel Manager',
            'permissions' => [
                'tms.dashboard.view',
                'tms.fuel.view',
                'tms.fuel.manage',
            ],
        ]);

        $user = User::create([
            'name'     => 'Fuel Manager',
            'email'    => 'fuel-manager@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $actions = (new TmsDashboardService)->quickActions($request);

        $this->assertCount(1, $actions);
        $this->assertSame('Add Fuel', $actions[0]['label']);
        $this->assertSame(route('admin.tms.fuel.create'), $actions[0]['url']);
    }

    public function test_transport_officer_sees_vehicle_create_action(): void
    {
        $role = Role::create([
            'name'        => 'Transport Officer',
            'permissions' => [
                'tms.dashboard.view',
                'tms.vehicles.view',
                'tms.vehicles.manage',
            ],
        ]);

        $user = User::create([
            'name'     => 'Officer',
            'email'    => 'officer@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $labels = collect((new TmsDashboardService)->quickActions($request))->pluck('label')->all();

        $this->assertContains('Add Vehicle', $labels);
        $this->assertNotContains('Add Fuel', $labels);
    }
}
