<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Role;
use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsRentalVendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
