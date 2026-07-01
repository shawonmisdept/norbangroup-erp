<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Hrm\HeadOfficeUserSeeder;
use Database\Seeders\Masters\HeadOfficeRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeadOfficeUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_head_office_user_seeder_creates_users_with_roles_and_permissions(): void
    {
        $this->seed([
            \Database\Seeders\Masters\FactorySeeder::class,
            \Database\Seeders\Hrm\HrmMasterDataSeeder::class,
            \Database\Seeders\Masters\DepartmentSeeder::class,
            \Database\Seeders\Masters\HeadOfficeOrgSeeder::class,
            HeadOfficeRoleSeeder::class,
            \Database\Seeders\Hrm\SalaryLegacySeeder::class,
            \Database\Seeders\Hrm\HeadOfficeEmployeeSeeder::class,
        ]);

        $this->seed(HeadOfficeUserSeeder::class);

        $factory = Factory::query()->where('name', 'Head Office')->firstOrFail();

        $this->assertGreaterThan(0, User::query()->where('factory_id', $factory->id)->count());

        $bari = User::query()->where('email', 'bari@norbangroup.com')->first();
        $this->assertNotNull($bari);
        $this->assertSame('Admin-GM', $bari->role?->name);
        $this->assertNotEmpty($bari->role?->permissions);

        $employee = Employee::query()
            ->where('factory_id', $factory->id)
            ->where('email', 'bari@norbangroup.com')
            ->first();

        $this->assertNotNull($employee);
        $this->assertTrue($bari->hasPermission('hrm.employees.manage'));

        $roleWithPermissions = Role::query()->where('name', 'Admin-GM')->first();
        $this->assertNotNull($roleWithPermissions);
        $this->assertContains('hrm.leave.approve', $roleWithPermissions->permissions ?? []);
    }
}
