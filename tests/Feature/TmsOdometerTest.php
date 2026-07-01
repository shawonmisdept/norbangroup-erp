<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Role;
use App\Models\Tms\TmsDailyOdometerLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmsOdometerTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $user;

    private TmsVehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-24 08:00:00');

        $this->factory = Factory::create(['name' => 'Test Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Admin',
            'permissions' => ['tms.trips.view', 'tms.trips.manage'],
        ]);

        $this->user = User::create([
            'name'       => 'Admin',
            'email'      => 'odometer@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);

        $this->vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Hiace',
            'reg_number'         => 'DHK-1111',
            'type'               => 'own',
            'fuel_type'          => 'petrol',
            'passenger_capacity' => 8,
            'status'             => 'available',
            'last_odometer_km'   => 1000,
        ]);
    }

    public function test_morning_km_saves_and_shows_on_index(): void
    {
        $this->actingAs($this->user)
            ->post(route('admin.tms.odometer.morning.store'), [
                'factory_id' => $this->factory->id,
                'vehicle_id' => $this->vehicle->id,
                'log_date'   => '2026-06-24',
                'morning_km' => 1050,
            ])
            ->assertRedirect(route('admin.tms.odometer.index'));

        $log = TmsDailyOdometerLog::first();

        $this->assertNotNull($log);
        $this->assertSame(1050.0, (float) $log->morning_km);
        $this->assertNull($log->evening_km);
        $this->assertNotNull($log->morning_recorded_at);
        $this->assertTrue($log->needsEvening());

        $this->actingAs($this->user)
            ->get(route('admin.tms.odometer.index'))
            ->assertOk()
            ->assertSee('1,050.00')
            ->assertSee($log->morningRecordedTime())
            ->assertSee('Evening Pending')
            ->assertSee('Evening KM');
    }

    public function test_evening_km_recorded_separately_without_edit(): void
    {
        Carbon::setTestNow('2026-06-24 18:00:00');

        $log = TmsDailyOdometerLog::create([
            'factory_id'          => $this->factory->id,
            'vehicle_id'          => $this->vehicle->id,
            'log_date'            => '2026-06-24',
            'morning_km'          => 1050,
            'morning_recorded_at' => '2026-06-24 08:00:00',
            'morning_entered_by'  => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.tms.odometer.evening.store', $log), [
                'evening_km' => 1120,
            ])
            ->assertRedirect(route('admin.tms.odometer.index'));

        $log->refresh();

        $this->assertSame(1120.0, (float) $log->evening_km);
        $this->assertSame('06:00 PM', $log->eveningRecordedTime());
        $this->assertSame(70.0, $log->dailyKm());
        $this->assertSame(1120.0, (float) $this->vehicle->fresh()->last_odometer_km);

        $this->actingAs($this->user)
            ->get(route('admin.tms.odometer.index'))
            ->assertOk()
            ->assertSee('1,120.00')
            ->assertSee('06:00 PM')
            ->assertSee('Complete')
            ->assertDontSee(route('admin.tms.odometer.evening.create', $log), false);
    }

    public function test_evening_km_cannot_be_recorded_twice(): void
    {
        $log = TmsDailyOdometerLog::create([
            'factory_id'         => $this->factory->id,
            'vehicle_id'         => $this->vehicle->id,
            'log_date'           => '2026-06-24',
            'morning_km'         => 1050,
            'evening_km'         => 1120,
            'morning_entered_by' => $this->user->id,
            'evening_entered_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.odometer.evening.create', $log))
            ->assertForbidden();
    }

    public function test_authority_can_edit_both_readings(): void
    {
        $log = TmsDailyOdometerLog::create([
            'factory_id'         => $this->factory->id,
            'vehicle_id'         => $this->vehicle->id,
            'log_date'           => '2026-06-24',
            'morning_km'         => 1050,
            'evening_km'         => 1120,
            'morning_entered_by' => $this->user->id,
            'evening_entered_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->put(route('admin.tms.odometer.update', $log), [
                'factory_id' => $this->factory->id,
                'vehicle_id' => $this->vehicle->id,
                'log_date'   => '2026-06-24',
                'morning_km' => 1048,
                'evening_km' => 1118,
            ])
            ->assertRedirect(route('admin.tms.odometer.index'));

        $log->refresh();

        $this->assertSame(1048.0, (float) $log->morning_km);
        $this->assertSame(1118.0, (float) $log->evening_km);
        $this->assertSame(70.0, $log->dailyKm());
    }

    public function test_authority_can_delete_odometer_log(): void
    {
        $log = TmsDailyOdometerLog::create([
            'factory_id'         => $this->factory->id,
            'vehicle_id'         => $this->vehicle->id,
            'log_date'           => '2026-06-24',
            'morning_km'         => 1050,
            'evening_km'         => 1120,
            'morning_entered_by' => $this->user->id,
            'evening_entered_by' => $this->user->id,
        ]);

        $this->vehicle->update(['last_odometer_km' => 1120]);

        $this->actingAs($this->user)
            ->delete(route('admin.tms.odometer.destroy', $log))
            ->assertRedirect(route('admin.tms.odometer.index'));

        $this->assertNull(TmsDailyOdometerLog::find($log->id));
        $this->assertSame(0.0, (float) $this->vehicle->fresh()->last_odometer_km);
    }

    public function test_odometer_reminder_command_notifies_missing_morning(): void
    {
        \App\Models\AppSetting::current()->update(['notify_popup_enabled' => true]);

        $this->artisan('tms:notify-odometer-reminders', ['--type' => 'morning'])
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
        ]);
    }
}
