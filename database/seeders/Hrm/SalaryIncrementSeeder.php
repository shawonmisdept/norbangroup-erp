<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryIncrementRule;
use Illuminate\Database\Seeder;

class SalaryIncrementSeeder extends Seeder
{
    public function run(): void
    {
        $factory = Factory::where('name', 'Norban Comtex Limited')->where('is_active', true)->first();

        if (! $factory) {
            return;
        }

        $grade = SalaryGrade::where('factory_id', $factory->id)->where('code', 'SR-01')->first();

        SalaryIncrementRule::updateOrCreate(
            [
                'factory_id' => $factory->id,
                'name'       => 'Annual Increment 5%',
            ],
            [
                'salary_grade_id'   => $grade?->id,
                'increment_type'    => 'percentage',
                'increment_value'   => 5,
                'min_tenure_months' => 12,
                'description'       => 'Standard annual increment for SR-01 staff with 12+ months service',
                'is_active'         => true,
            ]
        );

        SalaryIncrementRule::updateOrCreate(
            [
                'factory_id' => $factory->id,
                'name'       => 'Performance Bonus Fixed ৳1000',
            ],
            [
                'salary_grade_id'   => $grade?->id,
                'increment_type'    => 'fixed',
                'increment_value'   => 1000,
                'min_tenure_months' => 6,
                'description'       => 'Mid-year fixed increment for eligible staff',
                'is_active'         => true,
            ]
        );

        $this->command?->info('Seeded salary increment rules.');
    }
}
