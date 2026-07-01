<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Role;
use App\Models\Tms\TmsFuelLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TmsFuelTest extends TestCase
{
    use RefreshDatabase;

    private Factory $factory;

    private User $user;

    private TmsVehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->factory = Factory::create(['name' => 'Fuel Factory', 'is_active' => true]);

        $role = Role::create([
            'name'        => 'Fuel Admin',
            'permissions' => ['tms.fuel.view', 'tms.fuel.manage'],
        ]);

        $this->user = User::create([
            'name'       => 'Fuel User',
            'email'      => 'fuel@test.com',
            'password'   => 'password',
            'role_id'    => $role->id,
            'factory_id' => $this->factory->id,
        ]);

        $this->vehicle = TmsVehicle::create([
            'factory_id'         => $this->factory->id,
            'name'               => 'Pickup',
            'reg_number'         => 'DHK-FUEL',
            'type'               => 'own',
            'fuel_type'          => 'diesel',
            'passenger_capacity' => 4,
            'status'             => 'available',
        ]);
    }

    public function test_fuel_receipt_can_be_downloaded(): void
    {
        $path = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf')
            ->store('tms/fuel-receipts', 'public');

        $fuelLog = TmsFuelLog::create([
            'factory_id'     => $this->factory->id,
            'vehicle_id'     => $this->vehicle->id,
            'fuel_type'      => 'diesel',
            'quantity'       => 10,
            'unit'           => 'litre',
            'unit_price'     => 120,
            'amount'         => 1200,
            'paid_by'        => 'company',
            'receipt_path'   => $path,
            'receipt_number' => 'R-001',
            'created_by'     => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.fuel.receipt', $fuelLog))
            ->assertOk()
            ->assertDownload();
    }

    public function test_fuel_index_shows_receipt_link(): void
    {
        $path = UploadedFile::fake()->image('receipt.jpg')->store('tms/fuel-receipts', 'public');

        $fuelLog = TmsFuelLog::create([
            'factory_id'   => $this->factory->id,
            'vehicle_id'   => $this->vehicle->id,
            'fuel_type'    => 'diesel',
            'quantity'     => 5,
            'unit'         => 'litre',
            'unit_price'   => 120,
            'amount'       => 600,
            'paid_by'      => 'company',
            'receipt_path' => $path,
            'created_by'   => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.tms.fuel.index'))
            ->assertOk()
            ->assertSee('View');

        $this->actingAs($this->user)
            ->get(route('admin.tms.fuel.show', $fuelLog))
            ->assertOk()
            ->assertSee('Download Receipt')
            ->assertSee($fuelLog->receiptUrl(), false);
    }
}
