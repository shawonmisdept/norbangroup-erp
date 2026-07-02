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
            'mail_password'=> null,
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

    public function test_sending_quote_emails_client_when_enabled(): void
    {
        Mail::fake();

        AppSetting::current()->update([
            'notify_mail_client_on_status' => true,
            'mail_mailer'                  => 'log',
        ]);
        AppSetting::clearCache();

        $order = Order::create([
            'ref_code'  => 'NOR-QUOTE1',
            'name'      => 'Quote Client',
            'email'     => 'quote@example.com',
            'phone'     => '+8801712345678',
            'item_name' => 'Polo Shirt',
            'quantity'  => 1000,
            'status'    => 'Under Review',
        ]);

        $service = app(\App\Services\Commercial\QuoteBreakdownService::class);
        $breakdown = $service->template('woven', 'cm', 1000);

        foreach ($breakdown['sections'] as &$section) {
            foreach ($section['lines'] as &$line) {
                if ($line['code'] === 'cutting_making') {
                    $line['amount_pc'] = 150;
                    $line['enabled'] = true;
                }

                if (in_array($line['code'], ['factory_oh', 'factory_profit', 'rejection'], true)) {
                    $line['enabled'] = false;
                }
            }
        }
        unset($section, $line);

        $this->actingAs($this->admin)->patch(route('admin.requirements.workflow', $order), [
            'quote_garment_type' => 'woven',
            'quote_basis'        => 'cm',
            'quote_currency'     => 'BDT',
            'quote_breakdown'    => json_encode($breakdown),
            'quote_notes'        => 'Includes sampling cost.',
            'send_quote'         => 1,
        ])->assertRedirect();

        Mail::assertSent(\App\Mail\OrderQuoteMail::class, fn ($mail) => $mail->hasTo('quote@example.com'));
        Mail::assertSent(\App\Mail\StatusUpdatedMail::class);

        $order->refresh();
        $this->assertSame('Quoted', $order->status);
        $this->assertSame('150000.00', $order->quote_amount);
        $this->assertSame('150.0000', $order->quote_price_per_pc);
        $this->assertTrue($order->hasQuoteBreakdown());
    }

    public function test_fob_knit_breakdown_hides_logistics_for_cm_basis(): void
    {
        $order = Order::create([
            'ref_code'  => 'NOR-KNIT1',
            'name'      => 'Knit Buyer',
            'email'     => 'knit@example.com',
            'phone'     => '+8801712345678',
            'item_name' => 'T-Shirt',
            'quantity'  => 500,
            'status'    => 'Under Review',
        ]);

        $service = app(\App\Services\Commercial\QuoteBreakdownService::class);
        $breakdown = $service->template('knit', 'cm', 500);

        $this->actingAs($this->admin)->patch(route('admin.requirements.workflow', $order), [
            'quote_garment_type' => 'knit',
            'quote_basis'        => 'cm',
            'quote_breakdown'    => json_encode($breakdown),
        ])->assertRedirect();

        $order->refresh();

        $this->assertSame('knit', $order->quote_garment_type);
        $this->assertSame('cm', $order->quote_basis);
        $this->assertFalse(collect($order->quote_breakdown['sections'])->contains('code', 'logistics'));
    }

    public function test_custom_line_in_other_section_is_saved(): void
    {
        $order = Order::create([
            'ref_code'  => 'NOR-CUST1',
            'name'      => 'Custom Buyer',
            'email'     => 'custom@example.com',
            'phone'     => '+8801712345678',
            'item_name' => 'Jacket',
            'quantity'  => 200,
            'status'    => 'Under Review',
        ]);

        $service = app(\App\Services\Commercial\QuoteBreakdownService::class);
        $breakdown = $service->template('woven', 'fob', 200);

        foreach ($breakdown['sections'] as &$section) {
            foreach ($section['lines'] as &$line) {
                if (in_array($line['code'], ['factory_oh', 'factory_profit', 'rejection'], true)) {
                    $line['enabled'] = false;
                }
            }

            if ($section['code'] !== 'other') {
                continue;
            }

            $section['lines'][] = [
                'code'       => 'custom_special_finish',
                'label'      => 'Special Wash Finish',
                'calc'       => 'amount',
                'custom'     => true,
                'enabled'    => true,
                'amount_pc'  => 12.5,
            ];
        }
        unset($section, $line);

        $this->actingAs($this->admin)->patch(route('admin.requirements.workflow', $order), [
            'quote_garment_type' => 'woven',
            'quote_basis'        => 'fob',
            'quote_breakdown'    => json_encode($breakdown),
        ])->assertRedirect();

        $order->refresh();
        $other = collect($order->quote_breakdown['sections'])->firstWhere('code', 'other');
        $this->assertNotNull($other);
        $this->assertTrue(collect($other['lines'])->contains(fn ($line) => ($line['label'] ?? '') === 'Special Wash Finish'));
        $this->assertSame('2500.00', $order->quote_amount);
    }

    public function test_commercial_quote_editor_visible_only_for_commercial_quote_status(): void
    {
        $order = Order::create([
            'ref_code'  => 'NOR-STAT1',
            'name'      => 'Status Test',
            'email'     => 'status@example.com',
            'phone'     => '+8801712345678',
            'item_name' => 'Shirt',
            'quantity'  => 500,
            'status'    => 'Under Review',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.requirements.show', $order))
            ->assertOk()
            ->assertSee('Commercial Quote (locked)')
            ->assertDontSee('Cost Breakdown');

        $this->actingAs($this->admin)
            ->patch(route('admin.requirements.update', $order), [
                'status' => Order::STATUS_COMMERCIAL_QUOTE,
            ])
            ->assertRedirect(route('admin.requirements.show', $order) . '#commercial-quote');

        $this->actingAs($this->admin)
            ->get(route('admin.requirements.show', $order->fresh()))
            ->assertOk()
            ->assertSee('Cost Breakdown')
            ->assertDontSee('Commercial Quote (locked)');
    }

    public function test_assignment_only_update_does_not_clear_quote(): void
    {
        $order = Order::create([
            'ref_code'      => 'NOR-KEEP1',
            'name'          => 'Keep Quote',
            'email'         => 'keep@example.com',
            'phone'         => '+8801712345678',
            'item_name'     => 'Pant',
            'quantity'      => 100,
            'status'        => 'Quoted',
            'quote_amount'  => 50000,
            'quote_basis'   => 'cm',
            'quote_garment_type' => 'woven',
        ]);

        $this->actingAs($this->admin)->patch(route('admin.requirements.workflow', $order), [
            'assigned_to_user_id' => $this->admin->id,
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('50000.00', $order->quote_amount);
        $this->assertSame($this->admin->id, $order->assigned_to_user_id);
    }

    public function test_assignment_dropdown_lists_only_updatable_users_with_role_labels(): void
    {
        Role::create([
            'name'        => 'Viewer Only',
            'permissions' => ['orders.view', 'orders.download'],
        ]);

        User::create([
            'name'     => 'Read Only',
            'email'    => 'viewer-only@test.com',
            'password' => 'password',
            'role_id'  => Role::where('name', 'Viewer Only')->value('id'),
        ]);

        $managerRole = Role::create([
            'name'        => 'Commercial Manager',
            'permissions' => ['orders.view', 'orders.update'],
        ]);

        User::create([
            'name'     => 'Karim',
            'email'    => 'karim@test.com',
            'password' => 'password',
            'role_id'  => $managerRole->id,
        ]);

        $order = Order::create([
            'ref_code'  => 'NOR-ASSIGN',
            'name'      => 'Assign Test',
            'email'     => 'assign@example.com',
            'phone'     => '+8801712345678',
            'item_name' => 'Shirt',
            'status'    => 'New',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.requirements.show', $order));

        $response->assertOk()
            ->assertSee('Karim — Commercial Manager')
            ->assertSee('Test Admin — Test Admin')
            ->assertDontSee('Read Only — Viewer Only');
    }
}
