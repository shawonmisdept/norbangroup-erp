<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private EmployeePortalUser $portalUser;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'webpush.vapid.public_key' => 'BMEG1fLGTk9MBDcS0e8SC5M5jKZhK3Otl-vJKepzSj87D4AFdyaVEpI--oqsyjjA711_l6_Z3b7eVZvFHDGzzOA',
        ]);

        $factory = Factory::create(['name' => 'Push Factory', 'is_active' => true]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => $factory->code . '-00001',
            'name'          => 'Push Worker',
            'status'        => 'active',
        ]);

        $this->portalUser = EmployeePortalUser::create([
            'employee_id' => $employee->id,
            'password'    => 'secret-password',
            'is_active'   => true,
        ]);
    }

    public function test_guest_cannot_subscribe_to_push(): void
    {
        $this->postJson(route('employee.push.subscribe'), [
            'endpoint' => 'https://push.example.test/subscription',
            'keys'     => [
                'auth'   => 'auth-token',
                'p256dh' => 'p256dh-key',
            ],
        ])->assertRedirect(route('employee.login'));
    }

    public function test_employee_can_subscribe_and_unsubscribe_from_push(): void
    {
        $endpoint = 'https://push.example.test/subscription/abc123';

        $this->actingAs($this->portalUser, 'employee')
            ->postJson(route('employee.push.subscribe'), [
                'endpoint' => $endpoint,
                'keys'     => [
                    'auth'   => 'auth-token',
                    'p256dh' => 'p256dh-key',
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id'   => $this->portalUser->id,
            'subscribable_type' => EmployeePortalUser::class,
            'endpoint'          => $endpoint,
        ]);

        $this->actingAs($this->portalUser, 'employee')
            ->deleteJson(route('employee.push.unsubscribe'), [
                'endpoint' => $endpoint,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $endpoint,
        ]);
    }

    public function test_authenticated_employee_can_fetch_vapid_public_key(): void
    {
        $this->actingAs($this->portalUser, 'employee')
            ->getJson(route('employee.push.vapid-public-key'))
            ->assertOk()
            ->assertJson([
                'publicKey' => config('webpush.vapid.public_key'),
            ]);
    }
}
