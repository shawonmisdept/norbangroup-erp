<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Item;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequirementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Item::create([
            'code'      => 'ITM-001',
            'name'      => 'T-Shirt',
            'is_active' => true,
        ]);

        $role = Role::create([
            'name'        => 'Test Admin',
            'permissions' => ['orders.view', 'orders.update', 'orders.delete', 'orders.download'],
        ]);

        $this->admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin-requirements@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    public function test_mail_configuration_is_invalid_when_smtp_has_no_credentials(): void
    {
        AppSetting::current()->update([
            'mail_mailer'  => 'smtp',
            'mail_host'    => '127.0.0.1',
            'mail_port'    => 2525,
            'mail_username'=> null,
        ]);
        AppSetting::clearCache();

        $this->assertFalse(AppSetting::current()->canSendMail());
    }

    public function test_requirement_submission_succeeds_when_mail_is_misconfigured(): void
    {
        AppSetting::current()->update([
            'mail_mailer'                 => 'smtp',
            'mail_host'                   => '127.0.0.1',
            'mail_port'                   => 2525,
            'mail_username'               => null,
            'notify_mail_client_on_order' => true,
            'notify_mail_admin_on_order'  => true,
        ]);
        AppSetting::clearCache();

        $response = $this->post(route('orders.store'), [
            'name'      => 'Mail Fail Safe',
            'email'     => 'safe@example.com',
            'phone'     => '+8801712345678',
            'item_name' => 'T-Shirt',
        ]);

        $response->assertRedirect(route('orders.success'));
        $this->assertDatabaseHas('orders', ['email' => 'safe@example.com']);
    }

    public function test_public_requirement_submission_appears_in_admin_index(): void
    {
        Mail::fake();

        $response = $this->post(route('orders.store'), [
            'name'      => 'Jane Client',
            'company'   => 'Test Fashion Ltd',
            'email'     => 'jane@example.com',
            'phone'     => '+8801712345678',
            'item_name' => 'T-Shirt',
            'quantity'  => 500,
            'notes'     => 'Need sample first',
        ]);

        $response->assertRedirect(route('orders.success'));
        $this->assertDatabaseCount('orders', 1);

        $order = Order::first();
        $this->assertSame('Jane Client', $order->name);
        $this->assertSame('T-Shirt', $order->item_name);

        $this->actingAs($this->admin)
            ->get(route('admin.requirements.index'))
            ->assertOk()
            ->assertSee('Requirements Dashboard')
            ->assertSee($order->ref_code)
            ->assertSee('Jane Client')
            ->assertSee('T-Shirt');
    }

    public function test_admin_can_delete_requirement(): void
    {
        Mail::fake();

        $this->post(route('orders.store'), [
            'name'      => 'Delete Me',
            'email'     => 'delete@example.com',
            'phone'     => '+8801712345678',
            'item_name' => 'T-Shirt',
        ])->assertRedirect(route('orders.success'));

        $order = Order::first();

        $this->actingAs($this->admin)
            ->delete(route('admin.requirements.destroy', $order))
            ->assertRedirect(route('admin.requirements.index'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_viewer_cannot_delete_requirement(): void
    {
        $viewerRole = Role::create([
            'name'        => 'Viewer',
            'permissions' => ['orders.view'],
        ]);

        $viewer = User::create([
            'name'     => 'Viewer',
            'email'    => 'viewer-requirements@test.com',
            'password' => 'password',
            'role_id'  => $viewerRole->id,
        ]);

        $order = Order::create([
            'ref_code'  => 'NOR-TEST01',
            'name'      => 'Protected',
            'email'     => 'protected@example.com',
            'phone'     => '+8801712345678',
            'item_name' => 'T-Shirt',
            'status'    => 'New',
        ]);

        $this->actingAs($viewer)
            ->delete(route('admin.requirements.destroy', $order))
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_legacy_admin_orders_url_redirects_to_requirements(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/orders')
            ->assertRedirect('/admin/requirements');
    }
}
