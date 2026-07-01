<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsRentalDriverPortalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalPushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private TmsRentalDriverPortalUser $portalUser;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'webpush.vapid.public_key' => 'BMEG1fLGTk9MBDcS0e8SC5M5jKZhK3Otl-vJKepzSj87D4AFdyaVEpI--oqsyjjA711_l6_Z3b7eVZvFHDGzzOA',
        ]);

        $factory = Factory::create(['name' => 'Push Factory', 'is_active' => true]);

        $driver = TmsRentalDriver::create([
            'factory_id' => $factory->id,
            'name'       => 'Push Driver',
            'mobile'     => '01710000099',
            'status'     => 'active',
        ]);

        $this->portalUser = TmsRentalDriverPortalUser::create([
            'rental_driver_id' => $driver->id,
            'password'         => 'secret-password',
            'is_active'        => true,
        ]);
    }

    public function test_guest_cannot_subscribe_to_rental_push(): void
    {
        $this->postJson(route('rental.push.subscribe'), [
            'endpoint' => 'https://push.example.test/subscription',
            'keys'     => [
                'auth'   => 'auth-token',
                'p256dh' => 'p256dh-key',
            ],
        ])->assertRedirect(route('rental.login'));
    }

    public function test_rental_driver_can_subscribe_and_unsubscribe_from_push(): void
    {
        $endpoint = 'https://push.example.test/subscription/rental123';

        $this->actingAs($this->portalUser, 'rental_driver')
            ->postJson(route('rental.push.subscribe'), [
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
            'subscribable_type' => TmsRentalDriverPortalUser::class,
            'endpoint'          => $endpoint,
        ]);

        $this->actingAs($this->portalUser, 'rental_driver')
            ->deleteJson(route('rental.push.unsubscribe'), [
                'endpoint' => $endpoint,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $endpoint,
        ]);
    }

    public function test_authenticated_rental_driver_can_fetch_vapid_public_key(): void
    {
        $this->actingAs($this->portalUser, 'rental_driver')
            ->getJson(route('rental.push.vapid-public-key'))
            ->assertOk()
            ->assertJson([
                'publicKey' => config('webpush.vapid.public_key'),
            ]);
    }
}
