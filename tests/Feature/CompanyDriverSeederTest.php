<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Tms\TmsDriver;
use App\Models\Tms\TmsVehicle;
use Database\Seeders\Hrm\HeadOfficeEmployeeSeeder;
use Database\Seeders\Hrm\HrmMasterDataSeeder;
use Database\Seeders\Masters\FactorySeeder;
use Database\Seeders\Masters\HeadOfficeOrgSeeder;
use Database\Seeders\Tms\CompanyDriverSeeder;
use Database\Seeders\Tms\VehicleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyDriverSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_driver_seeder_links_thirteen_drivers_to_eighteen_vehicles(): void
    {
        $this->seed([
            FactorySeeder::class,
            HrmMasterDataSeeder::class,
            HeadOfficeOrgSeeder::class,
            HeadOfficeEmployeeSeeder::class,
            VehicleSeeder::class,
        ]);

        $this->seed(CompanyDriverSeeder::class);

        $this->assertSame(13, TmsDriver::count());
        $this->assertSame(18, DB::table('tms_driver_vehicles')->count());

        $ismail = Employee::where('employee_code', '1708')->firstOrFail();
        $driver = TmsDriver::where('employee_id', $ismail->id)->firstOrFail();

        $this->assertSame(3, $driver->vehicles()->count());
        $this->assertEqualsCanonicalizing(
            ['DM-GA-42-0117', 'DM-GHA-13-8951', 'DM-GHA-22-1042'],
            $driver->vehicles()->pluck('reg_number')->all()
        );
        $this->assertSame(
            'DM-GHA-22-1042',
            TmsVehicle::find($driver->primaryVehicleId())?->reg_number
        );

        $this->assertSame('01755511897', Employee::where('employee_code', '1710')->value('phone'));
        $this->assertSame('01755511669', Employee::where('employee_code', '1801')->value('phone'));
        $this->assertSame('01780475709', Employee::where('employee_code', '1887')->value('phone'));

        $jeep = TmsVehicle::where('reg_number', 'DM-GHA-11-8402')->firstOrFail();
        $this->assertSame(
            Employee::where('employee_code', '1872')->value('id'),
            TmsDriver::find($jeep->primary_driver_id)?->employee_id
        );
    }
}
