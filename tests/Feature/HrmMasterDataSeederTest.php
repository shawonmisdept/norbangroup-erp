<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\BiometricDevice;
use App\Models\Hrm\Building;
use App\Models\Hrm\EmploymentType;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\Line;
use App\Models\Hrm\Shift;
use App\Models\Hrm\WorkerCategory;
use Database\Seeders\Hrm\HrmMasterDataSeeder;
use Database\Seeders\Masters\FactorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmMasterDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_hrm_master_data_seeder_populates_defaults_per_factory(): void
    {
        $this->seed(FactorySeeder::class);
        $this->seed(HrmMasterDataSeeder::class);

        $factoryCount = Factory::where('is_active', true)->count();

        $this->assertSame(7, WorkerCategory::count());
        $this->assertSame(5, EmploymentType::count());
        $this->assertSame(8, LeaveType::count());
        $this->assertSame($factoryCount * 2, Building::where('is_active', true)->count());
        $this->assertSame($factoryCount * 13, Line::where('is_active', true)->count());
        $this->assertSame($factoryCount * 2, Shift::where('is_active', true)->count());
        $this->assertSame($factoryCount * 10, Holiday::count());
        $this->assertSame($factoryCount * 2, BiometricDevice::where('is_active', true)->count());

        $this->assertDatabaseHas('hrm_worker_categories', ['name' => 'Operator']);
        $this->assertDatabaseHas('hrm_leave_types', ['name' => 'Casual Leave (CL)', 'is_paid' => true]);
        $this->assertDatabaseHas('hrm_shifts', ['name' => 'Day Shift', 'start_time' => '08:00:00']);
    }

    public function test_hrm_master_data_seeder_is_idempotent(): void
    {
        $this->seed(FactorySeeder::class);
        $this->seed(HrmMasterDataSeeder::class);
        $this->seed(HrmMasterDataSeeder::class);

        $this->assertSame(7, WorkerCategory::count());
        $this->assertSame(5, EmploymentType::count());
        $this->assertSame(8, LeaveType::count());
    }
}
