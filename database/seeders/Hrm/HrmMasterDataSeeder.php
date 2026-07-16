<?php

namespace Database\Seeders\Hrm;

use Illuminate\Database\Seeder;

class HrmMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WorkerCategorySeeder::class,
            EmploymentTypeSeeder::class,
            LeaveTypeSeeder::class,
            LeavePolicySeeder::class,
            OrganizationSeeder::class,
            ShiftSeeder::class,
            HolidaySeeder::class,
            BiometricDeviceSeeder::class,
            PerformanceSeeder::class,
            HrLetterTemplateSeeder::class,
        ]);
    }
}
