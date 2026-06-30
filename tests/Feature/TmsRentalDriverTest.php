<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Role;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsRentalVendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TmsRentalDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_rental_driver_crud_with_vendor_dropdown(): void
    {
        $factory = Factory::create(['name' => 'Test Factory', 'is_active' => true]);

        $vendor = TmsRentalVendor::create([
            'factory_id'     => $factory->id,
            'name'           => 'ABC Transport',
            'mobile'         => '01712345678',
            'contact_person' => 'Manager',
            'status'         => 'active',
        ]);

        $role = Role::create([
            'name'        => 'TMS Admin',
            'permissions' => ['tms.rental_drivers.view', 'tms.rental_drivers.manage'],
        ]);

        $user = User::create([
            'name'       => 'Admin',
            'email'      => 'rental-drv@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);

        $this->actingAs($user)
            ->post(route('admin.tms.rental-drivers.store'), [
                'factory_id'       => $factory->id,
                'name'             => 'Karim Mia',
                'mobile'           => '01711111111',
                'license_number'   => 'DL-12345',
                'rental_vendor_id' => $vendor->id,
                'status'           => 'active',
            ])
            ->assertRedirect(route('admin.tms.rental-drivers.index'));

        $driver = TmsRentalDriver::first();

        $this->assertNotNull($driver);
        $this->assertSame($vendor->id, $driver->rental_vendor_id);
        $this->assertSame('ABC Transport', $driver->vendor_name);
        $this->assertSame('01712345678', $driver->vendor_contact);
        $this->assertSame('ABC Transport — 01712345678', $driver->vendorLabel());

        $this->actingAs($user)
            ->get(route('admin.tms.rental-drivers.index'))
            ->assertOk()
            ->assertSee('ABC Transport — 01712345678');

        $this->assertStringContainsString('Karim Mia', $driver->displayLabel());
        $this->assertTrue($driver->isActive());
    }

    public function test_rental_driver_photo_upload_and_display(): void
    {
        $factory = Factory::create(['name' => 'Test Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Admin',
            'permissions' => ['tms.rental_drivers.view', 'tms.rental_drivers.manage'],
        ]);

        $user = User::create([
            'name'       => 'Admin',
            'email'      => 'rental-photo@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);

        $photo = UploadedFile::fake()->image('driver.jpg', 400, 400);

        $this->actingAs($user)
            ->post(route('admin.tms.rental-drivers.store'), [
                'factory_id' => $factory->id,
                'name'       => 'Photo Driver',
                'mobile'     => '01722222222',
                'status'     => 'active',
                'photo'      => $photo,
            ])
            ->assertRedirect(route('admin.tms.rental-drivers.index'));

        $driver = TmsRentalDriver::first();

        $this->assertNotNull($driver);
        $this->assertNotNull($driver->photo);
        $this->assertNotNull($driver->photoUrl());
        $this->assertSame('PD', $driver->initials());

        $this->actingAs($user)
            ->get(route('admin.tms.rental-drivers.index'))
            ->assertOk()
            ->assertSee('Photo Driver');
    }

    public function test_rental_driver_show_modal(): void
    {
        $factory = Factory::create(['name' => 'Test Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'TMS Admin',
            'permissions' => ['tms.rental_drivers.view', 'tms.rental_drivers.manage'],
        ]);

        $user = User::create([
            'name'       => 'Admin',
            'email'      => 'rental-show@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $factory->id,
        ]);

        $driver = TmsRentalDriver::create([
            'factory_id'     => $factory->id,
            'name'           => 'Show Driver',
            'mobile'         => '01733333333',
            'license_number' => 'DL-999',
            'status'         => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('admin.tms.rental-drivers.show', $driver) . '?modal=1', [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertSee('Show Driver')
            ->assertSee('DL-999');
    }
}
