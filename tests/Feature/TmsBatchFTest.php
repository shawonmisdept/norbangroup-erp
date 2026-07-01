<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppGateway;
use App\Models\AppSetting;
use App\Models\Factory;
use App\Models\Role;
use App\Models\Tms\TmsGpsPosition;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use App\Services\Tms\TmsGpsService;
use App\Services\Tms\TmsMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsBatchFTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Factory::create(['name' => 'Batch F Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Batch F',
            'permissions' => ['tms.settings.view', 'tms.settings.manage', 'settings.manage'],
        ]);

        $this->user = User::create([
            'name'       => 'Batch F Admin',
            'email'      => 'batchf@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);
    }

    public function test_gps_index_page_shows_coming_soon_when_disabled(): void
    {
        TmsSetting::create(array_merge(
            ['factory_id' => $this->factory->id],
            TmsSetting::defaultValues()
        ));

        $this->actingAs($this->user)
            ->get(route('admin.tms.gps.index', ['factory_id' => $this->factory->id]))
            ->assertOk()
            ->assertSee('Coming soon')
            ->assertSee('GPS Tracking');
    }

    public function test_gps_stub_records_position_when_enabled(): void
    {
        TmsSetting::create(array_merge(
            TmsSetting::defaultValues(),
            ['factory_id' => $this->factory->id, 'gps_tracking_enabled' => true, 'gps_provider' => 'browser']
        ));

        $vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'GPS Car',
            'reg_number'         => 'GPS-1',
            'type'               => 'own',
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);

        $service = app(TmsGpsService::class);
        $this->assertTrue($service->isEnabled($this->factory->id));

        $pos = $service->recordStub($vehicle, 23.8103000, 90.4125000, null, 45.5);
        $this->assertNotNull($pos);
        $this->assertSame('stub', $pos->source);
        $this->assertSame(1, TmsGpsPosition::count());
    }

    public function test_gps_stub_skips_when_disabled(): void
    {
        TmsSetting::create(array_merge(
            ['factory_id' => $this->factory->id],
            TmsSetting::defaultValues()
        ));

        $vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'No GPS',
            'reg_number'         => 'NG-1',
            'type'               => 'own',
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);

        $this->assertNull(app(TmsGpsService::class)->recordStub($vehicle, 23.81, 90.41));
        $this->assertSame(0, TmsGpsPosition::count());
    }

    public function test_whatsapp_gateway_log_driver_sends(): void
    {
        AppSetting::current()->update(['whatsapp_provider' => 'log']);
        AppSetting::clearCache();

        $gateway = app(WhatsAppGateway::class);
        $this->assertTrue($gateway->send('01700000000', 'Test message'));
    }

    public function test_tms_messaging_uses_whatsapp_when_enabled(): void
    {
        $whatsapp = $this->createMock(WhatsAppGateway::class);
        $whatsapp->expects($this->once())->method('send')->willReturn(true);
        $this->app->instance(WhatsAppGateway::class, $whatsapp);

        AppSetting::current()->update([
            'notify_whatsapp_tms' => true,
            'whatsapp_provider'   => 'log',
        ]);
        AppSetting::clearCache();

        $employee = \App\Models\Hrm\Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'WA-E1',
            'name'          => 'WA User',
            'phone'         => '01700000003',
            'status'        => 'active',
        ]);

        $request = \App\Models\Tms\TmsTransportRequest::create([
            'factory_id'         => $this->factory->id,
            'employee_id'        => $employee->id,
            'pickup_location'    => 'Gate',
            'destination_custom' => 'City',
            'pickup_at'          => now()->addDay(),
            'purpose'            => 'Test',
            'passenger_count'    => 1,
            'status'             => 'rejected',
        ]);

        app(TmsMessagingService::class)->requestRejected($request->load('employee'));
    }

    public function test_tms_settings_saves_gps_options(): void
    {
        TmsSetting::create(array_merge(
            ['factory_id' => $this->factory->id],
            TmsSetting::defaultValues()
        ));

        $this->actingAs($this->user)
            ->put(route('admin.tms.settings.update'), [
                'factory_id'           => $this->factory->id,
                'office_start'         => '09:00',
                'office_end'           => '17:00',
                'ot_basis'             => 'global_office_time',
                'company_night_bill'   => 120,
                'company_holiday_duty_bill' => 320,
                'rental_ot_hourly_rate'=> 120,
                'rental_km_rate'       => 12,
                'weekend_days'         => [5, 6],
                'gps_tracking_enabled' => 1,
                'gps_provider'         => 'device_api',
            ])
            ->assertRedirect();

        $settings = TmsSetting::where('factory_id', $this->factory->id)->first();
        $this->assertTrue($settings->gps_tracking_enabled);
        $this->assertSame('device_api', $settings->gps_provider);
    }

    public function test_whatsapp_providers_are_registered_in_config(): void
    {
        $providers = config('whatsapp.providers');

        $this->assertArrayHasKey('meta_cloud', $providers);
        $this->assertArrayHasKey('sslwireless', $providers);
        $this->assertArrayHasKey('greenweb', $providers);
        $this->assertArrayHasKey('bulksmsbd', $providers);
        $this->assertArrayHasKey('custom', $providers);
    }

    public function test_can_send_whatsapp_for_sslwireless_with_token(): void
    {
        AppSetting::current()->update([
            'whatsapp_provider' => 'sslwireless',
            'whatsapp_api_token'  => encrypt('test-token'),
        ]);
        AppSetting::clearCache();

        $this->assertTrue(AppSetting::current()->canSendWhatsApp());
    }

    public function test_can_send_whatsapp_for_custom_requires_url(): void
    {
        AppSetting::current()->update([
            'whatsapp_provider'   => 'custom',
            'whatsapp_custom_url' => 'https://example.com/whatsapp',
        ]);
        AppSetting::clearCache();

        $this->assertTrue(AppSetting::current()->canSendWhatsApp());

        AppSetting::current()->update(['whatsapp_custom_url' => null]);
        AppSetting::clearCache();

        $this->assertFalse(AppSetting::current()->canSendWhatsApp());
    }

    public function test_whatsapp_factory_resolves_custom_gateway(): void
    {
        AppSetting::current()->update([
            'whatsapp_provider'   => 'custom',
            'whatsapp_custom_url' => 'https://example.com/send',
        ]);
        AppSetting::clearCache();

        $gateway = app(\App\Services\Whatsapp\WhatsAppGatewayFactory::class)->make();
        $this->assertInstanceOf(\App\Services\Whatsapp\HttpWhatsAppGateway::class, $gateway);
    }
}
